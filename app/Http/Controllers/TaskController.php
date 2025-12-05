<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request, Project $project = null): JsonResponse
    {
        $user = $request->user();
        
        $query = Task::with(['project', 'assignee', 'creator', 'subTasks']);
        
        // Filter by project
        $routeProject = $project ?? $request->route('project');
        if ($routeProject) {
            $projectId = is_object($routeProject) ? $routeProject->id : (string) $routeProject;
            $query->where('project_id', $projectId);
        } elseif ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        // Role-based filtering
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        } elseif ($user->isManager()) {
            $query->whereHas('project', function($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
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
     * Store a newly created task in storage.
     */
    public function store(Request $request, Project $project)
    {
        $routeProject = $project ?? $request->route('project');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => $routeProject ? 'sometimes' : 'required|exists:projects,id',
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'due_date' => 'nullable|date|after_or_equal:today',
            'estimated_time' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);
        
        try {
            // Determine project
            if ($routeProject) {
                $projectId = is_object($routeProject) ? $routeProject->id : (string) $routeProject;
                $project = Project::findOrFail($projectId);
            } else {
                $project = Project::findOrFail($validated['project_id']);
            }

            $task = $this->taskService->createTask($validated, $project);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Task created successfully',
                    'data' => new TaskResource($task)
                ], 201);
            }
            
            return redirect()->route('projects.show', $project->id)
                ->with('success', 'Tâche créée avec succès');
        } catch (\Exception $e) {
            Log::error('Error creating task: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error creating task',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la création de la tâche']);
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): JsonResponse
    {
        $task = $this->taskService->getTaskWithRelations($task);
        
        return response()->json([
            'data' => new TaskResource($task)
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        // Ensure the task belongs to the project
        abort_unless($task->project_id === $project->id, 404);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'sometimes|exists:projects,id',
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'due_date' => 'sometimes|date|after_or_equal:today',
            'estimated_time' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'progress' => 'sometimes|numeric|min:0|max:100',
        ]);
        
        try {
            $task = $this->taskService->updateTask($task, $validated);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Task updated successfully',
                    'data' => new TaskResource($task)
                ]);
            }
            
            return back()->with('success', 'Tâche mise à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Error updating task: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error updating task',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour de la tâche']);
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Request $request, Project $project, Task $task)
    {
        // Ensure the task belongs to the project
        abort_unless($task->project_id === $project->id, 404);
        
        try {
            $this->taskService->deleteTask($task);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Task deleted successfully'
                ], 204);
            }
            
            return redirect()->route('projects.show', $project->id)
                ->with('success', 'Tâche supprimée avec succès');
        } catch (\Exception $e) {
            Log::error('Error deleting task: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error deleting task',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la suppression de la tâche']);
        }
    }
    
    /**
     * Update task status.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'in_review', 'completed', 'blocked'])],
            'progress' => 'sometimes|numeric|min:0|max:100',
        ]);
        
        try {
            $task = $this->taskService->updateTaskStatus(
                $task,
                $validated['status'],
                $validated['progress'] ?? null
            );
            
            return response()->json([
                'message' => 'Task status updated successfully',
                'data' => new TaskResource($task)
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
