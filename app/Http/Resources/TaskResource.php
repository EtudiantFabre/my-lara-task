<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'priority' => $this->priority,
            'status' => $this->status,
            'progress' => (float) $this->progress,
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'estimated_hours' => $this->estimated_hours ? (float) $this->estimated_hours : null,
            'time_spent' => $this->time_spent ? (float) $this->time_spent : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'title' => $this->project->title,
                ];
            }),
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                    'email' => $this->assignee->email,
                    'avatar' => $this->assignee->avatar,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'avatar' => $this->creator->avatar,
                ];
            }),
            'sub_tasks' => $this->whenLoaded('subTasks', function () {
                return $this->subTasks->map(function ($subTask) {
                    return [
                        'id' => $subTask->id,
                        'title' => $subTask->title,
                        'status' => $subTask->status,
                        'progress' => (float) $subTask->progress,
                        'due_date' => $subTask->due_date?->format('Y-m-d'),
                        'created_at' => $subTask->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            'activity_logs' => $this->whenLoaded('activityLogs', function () {
                return $this->activityLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->description,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                        'user' => [
                            'id' => $log->causer->id,
                            'name' => $log->causer->name,
                            'avatar' => $log->causer->avatar,
                        ],
                        'properties' => $log->properties->toArray(),
                    ];
                });
            }),
        ];
    }
}
