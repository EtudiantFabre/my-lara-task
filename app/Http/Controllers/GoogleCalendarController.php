<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    protected $client;
    protected $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uris' => [route('google-calendar.callback')],
        ]);
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    /**
     * Redirige l'utilisateur vers la page d'autorisation Google
     */
    public function connect(Request $request)
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Gère le callback après l'autorisation Google
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('settings.integrations')
                ->with('error', 'Erreur lors de la connexion à Google: ' . $request->error);
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($request->code);
            
            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? 'Erreur inconnue');
            }

            $user = Auth::user();
            $user->google_calendar_token = $token;
            $user->google_calendar_connected = true;
            $user->save();

            return redirect()
                ->route('settings.integrations')
                ->with('success', 'Connexion à Google Calendar réussie !');

        } catch (\Exception $e) {
            Log::error('Erreur de connexion à Google Calendar: ' . $e->getMessage());
            
            return redirect()
                ->route('settings.integrations')
                ->with('error', 'Erreur lors de la connexion à Google Calendar: ' . $e->getMessage());
        }
    }

    /**
     * Déconnecte l'utilisateur de Google Calendar
     */
    public function disconnect(Request $request)
    {
        $user = Auth::user();
        
        // Révoquer le token d'accès
        if ($user->google_calendar_token) {
            try {
                $this->client->revokeToken($user->google_calendar_token['access_token'] ?? '');
            } catch (\Exception $e) {
                Log::error('Erreur lors de la révocation du token Google: ' . $e->getMessage());
            }
        }
        
        // Supprimer les informations de connexion
        $user->update([
            'google_calendar_token' => null,
            'google_calendar_connected' => false,
        ]);

        return redirect()
            ->route('settings.integrations')
            ->with('success', 'Déconnexion de Google Calendar réussie.');
    }

    /**
     * Synchronise un projet avec Google Calendar
     */
    public function syncProject(Request $request, Project $project)
    {
        $user = Auth::user();
        
        if (!$user->google_calendar_connected || !$user->google_calendar_token) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez d\'abord connecter votre compte Google Calendar.',
            ], 400);
        }

        try {
            $this->client->setAccessToken($user->google_calendar_token);
            
            // Rafraîchir le token s'il est expiré
            if ($this->client->isAccessTokenExpired()) {
                $token = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $user->google_calendar_token = array_merge($user->google_calendar_token, $token);
                $user->save();
                $this->client->setAccessToken($token);
            }
            
            $this->calendarService = new Calendar($this->client);
            
            // Créer un événement pour le projet
            $event = new Event([
                'summary' => "[Projet] " . $project->name,
                'description' => $project->description,
                'colorId' => $this->getRandomColorId(),
            ]);
            
            // Définir la date de début et de fin
            $start = new EventDateTime();
            $start->setDateTime($project->start_date->toIso8601String());
            $event->setStart($start);
            
            if ($project->end_date) {
                $end = new EventDateTime();
                $end->setDateTime($project->end_date->toIso8601String());
                $event->setEnd($end);
            }
            
            // Ajouter une alerte à 50% du temps alloué
            if ($project->start_date && $project->end_date) {
                $midpoint = $project->start_date->copy()->addHours(
                    $project->start_date->diffInHours($project->end_date) / 2
                );
                
                $reminder = new \Google_Service_Calendar_EventReminder();
                $reminder->setUseDefault(false);
                $reminder->setOverrides([
                    'method' => 'email',
                    'minutes' => 0,
                ]);
                
                $event->setReminders([
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 0],
                        ['method' => 'popup', 'minutes' => 30],
                    ],
                ]);
            }
            
            // Créer l'événement
            $calendarId = 'primary';
            $createdEvent = $this->calendarService->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all',
            ]);
            
            // Mettre à jour le projet avec l'ID de l'événement
            $project->update([
                'google_calendar_event_id' => $createdEvent->id,
                'google_calendar_link' => $createdEvent->htmlLink,
            ]);
            
            // Synchroniser les tâches du projet
            $this->syncProjectTasks($project);
            
            return response()->json([
                'success' => true,
                'message' => 'Projet synchronisé avec Google Calendar.',
                'event' => [
                    'id' => $createdEvent->id,
                    'htmlLink' => $createdEvent->htmlLink,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur de synchronisation avec Google Calendar: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation avec Google Calendar: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Synchronise les tâches d'un projet avec Google Calendar
     */
    protected function syncProjectTasks(Project $project)
    {
        $tasks = $project->tasks()
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->get();
            
        foreach ($tasks as $task) {
            $this->createOrUpdateTaskEvent($task);
        }
    }
    
    /**
     * Crée ou met à jour un événement pour une tâche
     */
    protected function createOrUpdateTaskEvent(Task $task)
    {
        try {
            $this->client->setAccessToken(Auth::user()->google_calendar_token);
            $this->calendarService = new Calendar($this->client);
            
            $event = new Event([
                'summary' => "[Tâche] " . $task->title,
                'description' => $task->description,
                'colorId' => $this->getRandomColorId(),
            ]);
            
            // Définir la date d'échéance
            $start = new EventDateTime();
            $start->setDateTime($task->due_date->toIso8601String());
            $event->setStart($start);
            
            if ($task->estimated_hours) {
                $endDate = $task->due_date->copy()->addHours($task->estimated_hours);
                $end = new EventDateTime();
                $end->setDateTime($endDate->toIso8601String());
                $event->setEnd($end);
            }
            
            // Définir les rappels
            $event->setReminders([
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 60 * 24], // 1 jour avant
                    ['method' => 'popup', 'minutes' => 60], // 1 heure avant
                ],
            ]);
            
            $calendarId = 'primary';
            
            // Si la tâche a déjà un ID d'événement, le mettre à jour
            if ($task->google_calendar_event_id) {
                $updatedEvent = $this->calendarService->events->update(
                    $calendarId,
                    $task->google_calendar_event_id,
                    $event,
                    ['sendUpdates' => 'all']
                );
                
                $task->update([
                    'google_calendar_link' => $updatedEvent->htmlLink,
                ]);
                
                return $updatedEvent;
            } 
            // Sinon, créer un nouvel événement
            else {
                $createdEvent = $this->calendarService->events->insert($calendarId, $event, [
                    'sendUpdates' => 'all',
                ]);
                
                $task->update([
                    'google_calendar_event_id' => $createdEvent->id,
                    'google_calendar_link' => $createdEvent->htmlLink,
                ]);
                
                return $createdEvent;
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation de la tâche avec Google Calendar: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Génère un ID de couleur aléatoire pour les événements
     */
    protected function getRandomColorId(): int
    {
        // Les couleurs disponibles dans Google Calendar (1-11)
        return rand(1, 11);
    }
}
