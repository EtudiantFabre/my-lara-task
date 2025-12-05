<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Get tasks for a given project (eager load subtasks)
     */
    public function getTasksByProject(Project $project)
    {
        return $project->tasks()->with('subTasks')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create a new task under a project
     */
    public function createTask(array $data, Project $project): Task
    {
        // Normalize estimated_time
        if (!isset($data['estimated_time']) && isset($data['estimated_hours'])) {
            $data['estimated_time'] = (float) $data['estimated_hours'];
        }

        $taskData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'not_started',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? 0,
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'assigned_to' => $data['assigned_to'] ?? Auth::id(),
            'progress' => 0,
        ];

        $task = Task::create($taskData);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($task)
            ->withProperties(['attributes' => $taskData])
            ->log('created');

        return $task->load(['project', 'assignee', 'creator', 'subTasks']);
    }

    /**
     * Update an existing task
     */
    public function updateTask(Task $task, array $data): Task
    {
        // Normalize estimated_time
        if (!isset($data['estimated_time']) && isset($data['estimated_hours'])) {
            $data['estimated_time'] = (float) $data['estimated_hours'];
        }

        $task->update($data);

        // If status is completed, set progress to 100%
        if (isset($data['status']) && $data['status'] === 'completed') {
            $task->update(['progress' => 100]);
        }

        activity()
            ->causedBy(Auth::user())
            ->performedOn($task)
            ->withProperties(['changes' => $data])
            ->log('updated');

        return $task->fresh()->load(['project', 'assignee', 'creator', 'subTasks']);
    }

    /**
     * Delete a task
     */
    public function deleteTask(Task $task): bool
    {
        activity()
            ->causedBy(Auth::user())
            ->performedOn($task)
            ->log('deleted');

        return $task->delete();
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Task $task, string $status, ?int $progress = null): Task
    {
        $data = ['status' => $status];

        // If status is completed, force progress to 100%
        if ($status === 'completed') {
            $data['progress'] = 100;
        } elseif ($progress !== null) {
            $data['progress'] = $progress;
        }

        $task->update($data);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($task)
            ->withProperties($data)
            ->log('status_updated');

        return $task->fresh()->load(['project', 'assignee', 'creator']);
    }

    /**
     * Get task with full relations
     */
    public function getTaskWithRelations(Task $task): Task
    {
        return $task->load([
            'project',
            'assignee',
            'creator',
            'subTasks',
            'activityLogs' => function($query) {
                $query->with('user')->latest()->take(10);
            }
        ]);
    }
}
