<?php

namespace App\Http\Controllers;

use App\Models\SubTask;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubTaskController extends Controller
{
    /**
     * Store a newly created subtask for a task.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_time' => 'required|numeric|min:0',
            'due_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        $validated['task_id'] = $task->id;
        $validated['assigned_to'] = $validated['assigned_to'] ?? Auth::id();

        $subTask = SubTask::create($validated);

        return response()->json([
            'message' => 'Sub-task created successfully',
            'data' => $subTask,
        ], 201);
    }

    /**
     * Update the specified subtask.
     */
    public function update(Request $request, Task $task, SubTask $subTask): JsonResponse
    {
        $this->authorize('update', $task);

        abort_unless($subTask->task_id === $task->id, 404);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'estimated_time' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
            'status' => 'sometimes|in:not_started,in_progress,completed',
        ]);

        $subTask->update($validated);

        return response()->json([
            'message' => 'Sub-task updated successfully',
            'data' => $subTask,
        ]);
    }

    /**
     * Toggle a subtask completion status.
     */
    public function toggle(Task $task, SubTask $subTask): JsonResponse
    {
        $this->authorize('update', $task);
        abort_unless($subTask->task_id === $task->id, 404);

        $subTask->status = $subTask->status === 'completed' ? 'not_started' : 'completed';
        $subTask->save();

        return response()->json([
            'message' => 'Sub-task status toggled',
            'data' => $subTask,
        ]);
    }

    /**
     * Remove the specified subtask.
     */
    public function destroy(Task $task, SubTask $subTask): JsonResponse
    {
        $this->authorize('update', $task);
        abort_unless($subTask->task_id === $task->id, 404);

        $subTask->delete();

        return response()->json(['message' => 'Sub-task deleted successfully']);
    }
}
