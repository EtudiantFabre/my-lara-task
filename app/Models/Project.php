<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes, LogsActivity;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'description',
        'start_date',
        'deadline',
        'progress',
        'status',
        'user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'progress' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the options for the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'start_date', 'deadline', 'status', 'progress'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Project has been {$eventName}");
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');;
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the URL to the project's profile photo.
     */
    public function getActivityLogName(): string
    {
        return 'project';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le modèle est valide
     */
    public function isValid()
    {
        $validator = \Validator::make($this->attributes, [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'deadline' => 'required|date|after:start_date',
            'status' => 'required|in:not_started,in_progress,on_hold,completed,cancelled',
            'progress' => 'required|numeric|min:0|max:100',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        }

        return true;
    }

    /**
     * Récupère les erreurs de validation
     */
    public function getErrors()
    {
        return $this->errors ?? new \Illuminate\Support\MessageBag();
    }

    /**
     * The error messages for the validation.
     */
    public static $rules = [
        'title' => 'required|string|max:100',
        'description' => 'nullable|string|max:1000',
        'start_date' => 'required|date',
        'deadline' => 'required|date|after:start_date',
        'status' => 'required|in:not_started,in_progress,on_hold,completed,cancelled',
        'progress' => 'required|numeric|min:0|max:100',
        'user_id' => 'required|exists:users,id'
    ];
}
