<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStoryListen extends Model
{
    use HasFactory;

    protected $table = 'user_story_listens';

    protected $fillable = [
        'user_id',
        'story_id',
        'listened_seconds',
        'is_completed',
        'opened_at',
        'last_listened_at',
    ];

    protected $casts = [
        'listened_seconds' => 'integer',
        'is_completed' => 'boolean',
        'opened_at' => 'datetime',
        'last_listened_at' => 'datetime',
    ];

    /**
     * Get the user that owns the listen record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story that was listened to.
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}

