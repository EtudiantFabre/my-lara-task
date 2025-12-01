<?php

namespace App\Http\Controllers;

use App\Models\SubTask;
use App\Models\Task;
use App\Services\SubTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubTaskController extends Controller
{
    protected $subTaskService;

    public function __construct(SubTaskService $subTaskService)
    {
        $this->subTaskService = $subTaskService;
    }

    /**
     * Store a newly created subtask for a task.
     */
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_time' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        try {
            $subTask = $this->subTaskService->createSubTask($validated, $task);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sub-task created successfully',
                    'data' => $subTask,
                ], 201);
            }
            
            return back()->with('success', 'Sous-tâche créée');
        } catch (\Exception $e) {
            Log::error('Error creating subtask: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error creating subtask',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la création de la sous-tâche']);
        }
    }

    /**
     * Update the specified subtask.
     */
    public function update(Request $request, Task $task, SubTask $subTask)
    {
        abort_unless($subTask->task_id === $task->id, 404);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'estimated_time' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'sometimes|date|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
            'status' => 'sometimes|in:not_started,in_progress,completed',
        ]);

        try {
            $subTask = $this->subTaskService->updateSubTask($subTask, $validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sub-task updated successfully',
                    'data' => $subTask,
                ]);
            }
            
            return back()->with('success', 'Sous-tâche mise à jour');
        } catch (\Exception $e) {
            Log::error('Error updating subtask: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error updating subtask',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour de la sous-tâche']);
        }
    }

    /**
     * Toggle a subtask completion status.
     */
    public function toggle(Task $task, SubTask $subTask)
    {
        abort_unless($subTask->task_id === $task->id, 404);

        try {
            $subTask = $this->subTaskService->toggleSubTaskStatus($subTask);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Sub-task status toggled',
                    'data' => $subTask,
                ]);
            }
            
            return back()->with('success', 'Statut de la sous-tâche mis à jour');
        } catch (\Exception $e) {
            Log::error('Error toggling subtask status: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Error toggling subtask status',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors du changement de statut']);
        }
    }

    /**
     * Remove the specified subtask.
     */
    public function destroy(Task $task, SubTask $subTask)
    {
        abort_unless($subTask->task_id === $task->id, 404);

        try {
            $this->subTaskService->deleteSubTask($subTask);

            if (request()->expectsJson()) {
                return response()->json(['message' => 'Sub-task deleted successfully']);
            }
            
            return back()->with('success', 'Sous-tâche supprimée');
        } catch (\Exception $e) {
            Log::error('Error deleting subtask: ' . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Error deleting subtask',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la suppression de la sous-tâche']);
        }
    }
}
