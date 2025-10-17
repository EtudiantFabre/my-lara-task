<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Task::with(['project', 'creator', 'subTasks'])
            ->where('assigned_to', $user->id);
        
        // Filtrage par projet si spécifié
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        // Filtrage par statut si spécifié
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $tasks = $query->latest()->paginate($request->per_page ?? 15);
        
        return response()->json([
            'data' => TaskResource::collection($tasks),
            'pagination' => [
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
            ]
        ]);
    }

    /**
 * Show the form for creating a new task.
 */
public function create(Project $project): JsonResponse
{
    $this->authorize('create', [Task::class, $project]);
    
    return response()->json([
        'project' => $project->only(['id', 'title']),
        'priorities' => ['low', 'medium', 'high'],
        'statuses' => [
            'not_started' => 'Non commencé',
            'in_progress' => 'En cours',
            'on_hold' => 'En attente',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ],
        'due_date' => now()->addDays(7)->format('Y-m-d') // Date d'échéance par défaut : 7 jours à partir d'aujourd'hui
    ]);
}

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        
        return response()->json([
            'task' => new TaskResource($task->load(['project'])),
            'priorities' => ['low', 'medium', 'high'],
            'statuses' => [
                'not_started' => 'Non commencé',
                'in_progress' => 'En cours',
                'on_hold' => 'En attente',
                'completed' => 'Terminé',
                'cancelled' => 'Annulé'
            ]
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
            'due_date' => 'required|date|after_or_equal:today',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $validated['created_by'] = Auth::id();
            $validated['assigned_to'] = Auth::id(); // L'utilisateur connecté est automatiquement assigné
            $validated['status'] = $validated['status'] ?? 'not_started';
            
            // Mapper estimated_hours vers estimated_time
            if (isset($validated['estimated_hours'])) {
                $validated['estimated_time'] = $validated['estimated_hours'];
                unset($validated['estimated_hours']);
            }
            
            $task = Task::create($validated);
            
            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->withProperties(['attributes' => $validated])
                ->log('created');
            
            return response()->json([
                'message' => 'Task created successfully',
                'data' => new TaskResource($task->load(['project', 'assignee', 'creator', 'subTasks']))
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating task: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        
        return response()->json([
            'data' => new TaskResource(
                $task->load([
                    'project', 
                    'assignee', 
                    'creator', 
                    'subTasks',
                    'activityLogs' => function($query) {
                        $query->with('user')->latest()->take(10);
                    }
                ])
            )
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'sometimes|exists:projects,id',
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
            'due_date' => 'sometimes|date|after_or_equal:today',
            'estimated_hours' => 'nullable|numeric|min:0',
            'progress' => 'sometimes|numeric|min:0|max:100',
        ]);
        
        try {
            // Toujours forcer l'assignation à l'utilisateur connecté
            $validated['assigned_to'] = Auth::id();
            
            // Si le statut est marqué comme terminé, mettre la progression à 100%
            if (isset($validated['status']) && $validated['status'] === 'completed') {
                $validated['progress'] = 100;
            }
            
            $task->update($validated);
            
            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->withProperties(['changes' => $validated])
                ->log('updated');
            
            return response()->json([
                'message' => 'Tâche mise à jour avec succès',
                'data' => new TaskResource($task->load(['project', 'creator', 'subTasks']))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating task: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la tâche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        
        try {
            // Enregistrer l'activité avant la suppression
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->log('deleted');
            
            $task->delete();
            
            return response()->json([
                'message' => 'Task deleted successfully'
            ], 204);
            
        } catch (\Exception $e) {
            Log::error('Error deleting task: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update task status.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        
        $validated = $request->validate([
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'])],
            'progress' => 'sometimes|numeric|min:0|max:100',
        ]);
        
        try {
            // Si le statut est marqué comme terminé, forcer la progression à 100%
            if ($validated['status'] === 'completed') {
                $validated['progress'] = 100;
            }
            
            $task->update($validated);
            
            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->withProperties(['status' => $validated['status'], 'progress' => $validated['progress'] ?? null])
                ->log('status_updated');
            
            return response()->json([
                'message' => 'Task status updated successfully',
                'data' => new TaskResource($task->load(['project', 'assignee', 'creator']))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating task status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
