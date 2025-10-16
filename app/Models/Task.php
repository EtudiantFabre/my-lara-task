<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    use SoftDeletes, HasUuids;
    protected $keyType = 'string'; // Specify key type as string

    protected $fillable = [
        'title',
        'description',
        'estimated_time',
        'time_spent',
        'progress',
        'status',
        'due_date',
        'project_id',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'estimated_time' => 'float',
        'time_spent' => 'float',
        'progress' => 'float',
        'due_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class);
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
        static::updating(function ($task) {
            // Mettre à jour la progression du projet lorsque la tâche est mise à jour
            if ($task->isDirty('progress')) {
                $project = $task->project;
                $project->progress = $project->tasks()->avg('progress');
                $project->save();
            }
        });
    }
}
