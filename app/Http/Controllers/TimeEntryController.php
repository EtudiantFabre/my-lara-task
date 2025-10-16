<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TimeEntryController extends Controller
{
    /**
     * Affiche la liste des entrées de temps
     */
    public function index(): AnonymousResourceCollection
    {
        $entries = TimeEntry::where('user_id', Auth::id())
            ->with('task')
            ->latest('start_time')
            ->paginate(15);

        return TimeEntryResource::collection($entries);
    }

    /**
     * Crée une nouvelle entrée de temps
     */
    public function store(StoreTimeEntryRequest $request): TimeEntryResource
    {
        $validated = $request->validated();
        $task = Task::findOrFail($validated['task_id']);

        $this->authorize('view', $task->project);

        $entry = new TimeEntry([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'start_time' => now(),
            'description' => $validated['description'] ?? null,
        ]);

        // Arrête toute entrée en cours
        TimeEntry::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->update(['end_time' => now()]);

        $entry->save();

        return new TimeEntryResource($entry->load('task'));
    }

    /**
     * Met à jour une entrée de temps existante
     */
    public function update(StoreTimeEntryRequest $request, TimeEntry $timeEntry): TimeEntryResource
    {
        $this->authorize('update', $timeEntry);

        $validated = $request->validated();
        
        $timeEntry->update([
            'start_time' => $validated['start_time'] ?? $timeEntry->start_time,
            'end_time' => $validated['end_time'] ?? $timeEntry->end_time,
            'description' => $validated['description'] ?? $timeEntry->description,
        ]);

        return new TimeEntryResource($timeEntry->load('task'));
    }

    /**
     * Supprime une entrée de temps
     */
    public function destroy(TimeEntry $timeEntry): Response
    {
        $this->authorize('delete', $timeEntry);
        
        $timeEntry->delete();
        
        return response()->noContent();
    }

    /**
     * Récupère les entrées pour une date spécifique
     */
    public function byDate(string $date): AnonymousResourceCollection
    {
        $date = Carbon::parse($date);
        
        $entries = TimeEntry::where('user_id', Auth::id())
            ->whereDate('start_time', $date)
            ->with('task')
            ->orderBy('start_time')
            ->get();

        return TimeEntryResource::collection($entries);
    }

    /**
     * Récupère les entrées pour un projet spécifique
     */
    public function byProject(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $entries = TimeEntry::whereHas('task', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })
            ->where('user_id', Auth::id())
            ->with('task')
            ->latest('start_time')
            ->get();

        return TimeEntryResource::collection($entries);
    }

    /**
     * Récupère les entrées pour une tâche spécifique
     */
    public function byTask(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task->project);

        $entries = $task->timeEntries()
            ->where('user_id', Auth::id())
            ->latest('start_time')
            ->get();

        return TimeEntryResource::collection($entries);
    }

    /**
     * Démarre un nouveau chronomètre pour une tâche
     */
    public function start(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        // Arrête toute entrée en cours
        TimeEntry::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->update(['end_time' => now()]);

        $entry = new TimeEntry([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'start_time' => now(),
            'description' => $request->input('description'),
        ]);

        $entry->save();

        return response()->json([
            'message' => 'Chronomètre démarré',
            'entry' => new TimeEntryResource($entry->load('task'))
        ]);
    }

    /**
     * Arrête le chronomètre en cours
     */
    public function stop(TimeEntry $timeEntry): JsonResponse
    {
        $this->authorize('update', $timeEntry);

        if ($timeEntry->end_time) {
            return response()->json([
                'message' => 'Cette entrée est déjà terminée',
            ], 400);
        }

        $timeEntry->update(['end_time' => now()]);

        return response()->json([
            'message' => 'Chronomètre arrêté',
            'entry' => new TimeEntryResource($timeEntry->load('task'))
        ]);
    }
}
