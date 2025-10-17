<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Task::with(['project', 'assignee', 'creator', 'subTasks']);
        
        // Filtrage par projet si spécifié
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        // Filtrage par statut si spécifié
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrage par utilisateur assigné si spécifié
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        // Pour les employés, ne montrer que leurs tâches assignées
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        } 
        // Pour les managers, montrer les tâches de leurs projets
        elseif ($user->isManager()) {
            $query->whereHas('project', function($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        }
        // Les admins voient tout
        
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
     * Store a newly created task in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
            'due_date' => 'nullable|date|after_or_equal:today',
            'estimated_time' => 'required|numeric|min:0',
        ]);
        
        try {
            $validated['created_by'] = Auth::id();
            $validated['assigned_to'] = $validated['assigned_to'] ?? Auth::id();
            $validated['status'] = $validated['status'] ?? 'not_started';
            
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
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
            'due_date' => 'sometimes|date|after_or_equal:today',
            'estimated_time' => 'sometimes|numeric|min:0',
            'progress' => 'sometimes|numeric|min:0|max:100',
        ]);
        
        try {
            $task->update($validated);
            
            // Si le statut est marqué comme terminé, mettre la progression à 100%
            if (isset($validated['status']) && $validated['status'] === 'completed') {
                $task->update(['progress' => 100]);
            }
            
            // Enregistrer l'activité
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->withProperties(['changes' => $validated])
                ->log('updated');
            
            return response()->json([
                'message' => 'Task updated successfully',
                'data' => new TaskResource($task->load(['project', 'assignee', 'creator', 'subTasks']))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating task: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating task',
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
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
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
