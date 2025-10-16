<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'message',
        'sent_at',
        'user_id',
        'notifiable_id',
        'notifiable_type',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsSent(): bool
    {
        return $this->update(['sent_at' => now()]);
    }

    public function isSent(): bool
    {
        return ! is_null($this->sent_at);
    }
}
