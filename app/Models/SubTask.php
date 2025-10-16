<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SubTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'estimated_time',
        'time_spent',
        'status',
        'due_date',
        'task_id',
        'assigned_to',
    ];

    protected $casts = [
        'estimated_time' => 'float',
        'time_spent' => 'float',
        'due_date' => 'date',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    protected static function booted()
    {
        static::updating(function ($subTask) {
            // Mettre à jour la progression de la tâche parente lorsque la sous-tâche est mise à jour
            if ($subTask->isDirty('status') || $subTask->isDirty('progress')) {
                $task = $subTask->task;
                $completedSubtasks = $task->subTasks()->where('status', 'completed')->count();
                $totalSubtasks = $task->subTasks()->count();
                
                if ($totalSubtasks > 0) {
                    $task->progress = ($completedSubtasks / $totalSubtasks) * 100;
                    $task->save();
                }
            }
        });
    }
}
