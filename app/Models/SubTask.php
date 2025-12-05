<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SubTask extends Model
{
    use SoftDeletes, HasUuids;

    protected $keyType = 'string';

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
        // Fonction utilitaire pour recalculer la progression d'une tâche depuis ses sous-tâches
        $recalcTaskProgress = function ($subTask) {
            $task = $subTask->task;
            if ($task) {
                $totalSubtasks = (int) $task->subTasks()->count();
                if ($totalSubtasks > 0) {
                    $completedSubtasks = (int) $task->subTasks()->where('status', 'completed')->count();
                    $task->progress = ($completedSubtasks / $totalSubtasks) * 100;
                } else {
                    // S'il n'y a plus de sous-tâches, ramener la progression à 0 par défaut
                    $task->progress = 0;
                }
                $task->save();
            }
        };

        static::updating(function ($subTask) use ($recalcTaskProgress) {
            // Mettre à jour la progression de la tâche parente lorsque la sous-tâche change de statut
            if ($subTask->isDirty('status')) {
                $recalcTaskProgress($subTask);
            }
        });

        // Cohérence: recalculer les temps et la progression de la tâche depuis les sous‑tâches après chaque sauvegarde/suppression
        $recalcTaskTimes = function ($subTask) use ($recalcTaskProgress) {
            $task = $subTask->task;
            if ($task) {
                $task->estimated_time = (float) $task->subTasks()->sum('estimated_time');
                $task->time_spent = (float) $task->subTasks()->sum('time_spent');
                $task->save();
                // Recalculer aussi la progression (pour les cas d'ajout/suppression de sous-tâche)
                $recalcTaskProgress($subTask);
            }
        };

        static::saved($recalcTaskTimes);
        static::deleted($recalcTaskTimes);
    }
}
