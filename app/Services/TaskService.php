<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Get tasks for a project with filters
     */
    public function getTasksForProject(Project $project, array $filters = [])
    {
        $query = Task::where('project_id', $project->id)
            ->with(['assignee', 'creator', 'subTasks']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest()->get();
    }

    /**
     * Create a new task
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
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'assigned_to' => $data['assigned_to'] ?? Auth::id(),
            'status' => $data['status'] ?? 'not_started',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? 0,
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
        // Normalize estimated_time on update
        if (!isset($data['estimated_time']) && isset($data['estimated_hours'])) {
            $data['estimated_time'] = (float) $data['estimated_hours'];
        }

        $task->update($data);

        // If status is completed, set progress to 100%
        if (isset($data['status']) && $data['status'] === 'completed') {
            $task->update(['progress' => 100]);
        }

        // Log activity
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
        // Log activity before deletion
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
