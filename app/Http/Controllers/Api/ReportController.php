<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Affiche les statistiques de productivité
     */
    public function productivity(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', now()->endOfWeek()->toDateString());
        $userId = $request->input('user_id', Auth::id());
        $projectId = $request->input('project_id');

        $query = TimeEntry::where('user_id', $userId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))) as total_seconds')
            )
            ->groupBy('date');

        if ($projectId) {
            $query->whereHas('task', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            });
        }

        $timeData = $query->get()
            ->keyBy('date')
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'hours' => round($item->total_seconds / 3600, 2),
                ];
            });

        // Remplir les dates manquantes avec 0
        $period = CarbonPeriod::create($startDate, $endDate);
        $result = [];
        
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $result[] = $timeData->get($dateStr, [
                'date' => $dateStr,
                'hours' => 0,
            ]);
        }

        return response()->json([
            'data' => $result,
            'total_hours' => round($timeData->sum('hours'), 2),
            'average_daily_hours' => round($timeData->avg('hours'), 2),
        ]);
    }

    /**
     * Affiche l'utilisation du temps par catégorie
     */
    public function timeUsage(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', now()->endOfWeek()->toDateString());
        $userId = $request->input('user_id', Auth::id());

        $timeByProject = TimeEntry::join('tasks', 'time_entries.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('time_entries.user_id', $userId)
            ->whereBetween('time_entries.start_time', [$startDate, $endDate])
            ->select(
                'projects.id',
                'projects.name',
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, time_entries.start_time, COALESCE(time_entries.end_time, NOW()))) as total_seconds')
            )
            ->groupBy('projects.id', 'projects.name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'hours' => round($item->total_seconds / 3600, 2),
                ];
            });

        $totalSeconds = $timeByProject->sum('total_seconds');
        
        $timeByProject = $timeByProject->map(function ($item) use ($totalSeconds) {
            $item['percentage'] = $totalSeconds > 0 
                ? round(($item['hours'] * 3600 / $totalSeconds) * 100, 1)
                : 0;
            return $item;
        });

        return response()->json([
            'data' => $timeByProject,
            'total_hours' => round($totalSeconds / 3600, 2),
        ]);
    }

    /**
     * Affiche la progression des projets
     */
    public function projectProgress(): JsonResponse
    {
        $projects = Project::withCount(['tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->withCount('tasks')
            ->where('user_id', Auth::id())
            ->orWhereHas('members', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'total_tasks' => $project->tasks_count,
                    'completed_tasks' => $project->completed_tasks_count,
                    'progress' => $project->tasks_count > 0 
                        ? round(($project->completed_tasks_count / $project->tasks_count) * 100) 
                        : 0,
                ];
            });

        return response()->json($projects);
    }

    /**
     * Affiche les performances des utilisateurs
     */
    public function userPerformance(): JsonResponse
    {
        // Seul un administrateur ou un manager peut voir cette statistique
        if (!Auth::user()->hasRole(['admin', 'manager'])) {
            abort(403, 'Non autorisé');
        }

        $users = User::withCount([
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'tasks as overdue_tasks_count' => function ($query) {
                    $query->where('due_date', '<', now())
                        ->where('status', '!=', 'completed');
                },
                'timeEntries as total_hours' => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))), 0)'));
                },
            ])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['employee', 'manager']);
            })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'completed_tasks' => $user->completed_tasks_count,
                    'overdue_tasks' => $user->overdue_tasks_count,
                    'total_hours' => round($user->total_hours / 3600, 1),
                ];
            });

        return response()->json($users);
    }

    /**
     * Exporte les données au format CSV
     */
    public function export(Request $request): JsonResponse
    {
        $type = $request->input('type', 'time_entries');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->input('user_id', Auth::id());

        $data = [];
        $filename = '';

        switch ($type) {
            case 'time_entries':
                $data = $this->getTimeEntriesData($userId, $startDate, $endDate);
                $filename = 'time-entries-' . now()->format('Y-m-d') . '.csv';
                break;
            case 'tasks':
                $data = $this->getTasksData($userId, $startDate, $endDate);
                $filename = 'tasks-' . now()->format('Y-m-d') . '.csv';
                break;
            default:
                abort(400, 'Type d\'export non valide');
        }

        // En production, vous voudrez probablement générer un vrai fichier CSV
        // et le stocker ou le télécharger directement
        return response()->json([
            'filename' => $filename,
            'data' => $data,
        ]);
    }

    /**
     * Récupère les données des entrées de temps pour l'export
     */
    private function getTimeEntriesData(int $userId, ?string $startDate, ?string $endDate): array
    {
        $query = TimeEntry::with(['task.project', 'user'])
            ->where('user_id', $userId);

        if ($startDate) {
            $query->whereDate('start_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }

        return $query->get()->map(function ($entry) {
            return [
                'Date' => $entry->start_time->format('Y-m-d'),
                'Heure de début' => $entry->start_time->format('H:i'),
                'Heure de fin' => $entry->end_time ? $entry->end_time->format('H:i') : 'En cours',
                'Durée (heures)' => $entry->duration_in_hours,
                'Projet' => $entry->task->project->name,
                'Tâche' => $entry->task->title,
                'Description' => $entry->description,
            ];
        })->toArray();
    }

    /**
     * Récupère les données des tâches pour l'export
     */
    private function getTasksData(int $userId, ?string $startDate, ?string $endDate): array
    {
        $query = Task::with(['project', 'assignee'])
            ->where('user_id', $userId);

        if ($startDate) {
            $query->whereDate('due_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('due_date', '<=', $endDate);
        }

        return $query->get()->map(function ($task) {
            return [
                'Tâche' => $task->title,
                'Projet' => $task->project->name,
                'Statut' => $this->getStatusLabel($task->status),
                'Priorité' => $this->getPriorityLabel($task->priority),
                'Date d\'échéance' => $task->due_date ? $task->due_date->format('Y-m-d') : 'Non définie',
                'Temps estimé (heures)' => $task->estimated_hours,
                'Temps passé (heures)' => $task->time_entries_sum_duration_seconds 
                    ? round($task->time_entries_sum_duration_seconds / 3600, 2) 
                    : 0,
                'Assigné à' => $task->assignee ? $task->assignee->name : 'Non assigné',
            ];
        })->toArray();
    }

    /**
     * Traduit le statut en libellé lisible
     */
    private function getStatusLabel(string $status): string
    {
        $statuses = [
            'todo' => 'À faire',
            'in_progress' => 'En cours',
            'in_review' => 'En révision',
            'completed' => 'Terminé',
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Traduit la priorité en libellé lisible
     */
    private function getPriorityLabel(string $priority): string
    {
        $priorities = [
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
        ];

        return $priorities[$priority] ?? $priority;
    }
}
