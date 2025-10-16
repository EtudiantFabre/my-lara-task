<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date->format('Y-m-d'),
            'deadline' => $this->deadline->format('Y-m-d'),
            'progress' => (float) $this->progress,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar,
            ],
            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'progress' => (float) $task->progress,
                        'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                        'sub_tasks_count' => $task->subTasks->count(),
                        'completed_sub_tasks_count' => $task->subTasks->where('status', 'completed')->count(),
                    ];
                });
            }),
            'activity_logs' => $this->whenLoaded('activityLogs', function () {
                return $this->activityLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $log->description,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                        'user' => [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'avatar' => $log->user->avatar,
                        ],
                    ];
                });
            }),
        ];
    }
}
