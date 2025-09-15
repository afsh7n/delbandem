<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'phone_number',
        'password',
        'verification_code',
        'photo',
        'role',
        'token',
        'favorites',
        'rated_stories',
        'has_open_ticket',
        'password_changed_at',
        'password_reset_expires',
        'changed_password_after',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'has_open_ticket',
        'rated_stories',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verification_code' => 'json',
            'token' => 'json',
            'favorites' => 'array',
            'rated_stories' => 'array',
            'has_open_ticket' => 'boolean',
            'password_changed_at' => 'datetime',
            'password_reset_expires' => 'datetime',
            'changed_password_after' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function favoriteStories()
    {
        return $this->belongsToMany(Story::class, 'user_favorites', 'user_id', 'story_id');
    }
}
