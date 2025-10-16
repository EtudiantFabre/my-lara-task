<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'start_time',
        'end_time',
        'duration',
        'description',
        'task_id',
        'user_id',
        'project_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer', // en secondes
    ];

    /**
     * Relation avec l'utilisateur qui a enregistré le temps
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la tâche associée
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relation avec le projet associé
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Calcule la durée en secondes
     */
    public function calculateDuration(): int
    {
        if (!$this->end_time) {
            $this->duration = now()->diffInSeconds($this->start_time);
        } else {
            $this->duration = $this->end_time->diffInSeconds($this->start_time);
        }
        
        return $this->duration;
    }

    /**
     * Formate la durée en heures:minutes:secondes
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Démarre un nouveau chronomètre
     */
    public static function startNew(int $userId, int $taskId, ?string $description = null): self
    {
        // Arrête tout chronomètre en cours pour cet utilisateur
        self::stopRunningTimer($userId);

        return self::create([
            'user_id' => $userId,
            'task_id' => $taskId,
            'project_id' => Task::findOrFail($taskId)->project_id,
            'start_time' => now(),
            'description' => $description,
        ]);
    }

    /**
     * Arrête le chronomètre en cours pour un utilisateur
     */
    public static function stopRunningTimer(int $userId): ?self
    {
        $runningTimer = self::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if ($runningTimer) {
            $runningTimer->end_time = now();
            $runningTimer->calculateDuration();
            $runningTimer->save();
            return $runningTimer;
        }

        return null;
    }
}
