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
    public function store(Request $request, Task $task)
    {

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Accepter estimated_time ou estimated_hours du front (nullable toléré)
            'estimated_time' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        $validated['task_id'] = $task->id;
        $validated['assigned_to'] = $validated['assigned_to'] ?? Auth::id();
        $validated['status'] = 'not_started';
        $validated['estimated_time'] = isset($validated['estimated_time'])
            ? (float) $validated['estimated_time']
            : (float) ($validated['estimated_hours'] ?? 0);

        $subTask = SubTask::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sub-task created successfully',
                'data' => $subTask,
            ], 201);
        }
        return back()->with('success', 'Sous-tâche créée');
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

        if (!array_key_exists('estimated_time', $validated)) {
            $validated['estimated_time'] = isset($validated['estimated_hours'])
                ? (float) $validated['estimated_hours']
                : null;
        }

        $subTask->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sub-task updated successfully',
                'data' => $subTask,
            ]);
        }
        return back()->with('success', 'Sous-tâche mise à jour');
    }

    /**
     * Toggle a subtask completion status.
     */
    public function toggle(Task $task, SubTask $subTask)
    {
        abort_unless($subTask->task_id === $task->id, 404);

        $subTask->status = $subTask->status === 'completed' ? 'not_started' : 'completed';
        $subTask->save();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Sub-task status toggled',
                'data' => $subTask,
            ]);
        }
        return back()->with('success', 'Statut de la sous-tâche mis à jour');
    }

    /**
     * Remove the specified subtask.
     */
    public function destroy(Task $task, SubTask $subTask)
    {
        abort_unless($subTask->task_id === $task->id, 404);

        $subTask->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Sub-task deleted successfully']);
        }
        return back()->with('success', 'Sous-tâche supprimée');
    }
}
