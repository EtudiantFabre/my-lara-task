<?php

namespace App\Services;

use App\Models\SubTask;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubTaskService
{
    /**
     * Create a new subtask
     */
    public function createSubTask(array $data, Task $task): SubTask
    {
        $subTaskData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'task_id' => $task->id,
            'status' => $data['status'] ?? 'not_started',
            'due_date' => $data['due_date'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? $data['estimated_hours'] ?? 0,
        ];

        $subTask = SubTask::create($subTaskData);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($subTask)
            ->withProperties(['attributes' => $subTaskData])
            ->log('created');

        return $subTask;
    }

    /**
     * Update an existing subtask
     */
    public function updateSubTask(SubTask $subTask, array $data): SubTask
    {
        // Normalize estimated_time
        if (!isset($data['estimated_time']) && isset($data['estimated_hours'])) {
            $data['estimated_time'] = (float) $data['estimated_hours'];
        }

        $subTask->update($data);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($subTask)
            ->withProperties(['changes' => $data])
            ->log('updated');

        return $subTask->fresh();
    }

    /**
     * Delete a subtask
     */
    public function deleteSubTask(SubTask $subTask): bool
    {
        // Log activity before deletion
        activity()
            ->causedBy(Auth::user())
            ->performedOn($subTask)
            ->log('deleted');

        return $subTask->delete();
    }

    /**
     * Toggle subtask status (completed/not_started)
     */
    public function toggleSubTaskStatus(SubTask $subTask): SubTask
    {
        $newStatus = $subTask->status === 'completed' ? 'not_started' : 'completed';
        
        $subTask->update(['status' => $newStatus]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($subTask)
            ->withProperties(['status' => $newStatus])
            ->log('status_toggled');

        return $subTask->fresh();
    }
}
