<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Morilog\Jalali\Jalalian;

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

    /**
     * Get the user's name attribute.
     * If name is null, return phone_number as fallback.
     */
    public function getNameAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }
        
        // Fallback to phone_number if name is null
        $phoneNumber = $this->attributes['phone_number'] ?? null;
        if ($phoneNumber) {
            return $phoneNumber;
        }
        
        // Final fallback
        return 'کاربر';
    }

    /**
     * Get user name for Filament (ensures string is always returned)
     */
    public function getUserName(): string
    {
        return $this->name;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function favoriteStories()
    {
        return $this->belongsToMany(Story::class, 'user_favorites', 'user_id', 'story_id');
    }

    /**
     * Get the story listens for the user.
     */
    public function storyListens()
    {
        return $this->hasMany(UserStoryListen::class);
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '>', now())
            ->latest();
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Get subscription info for API response
     */
    public function getSubscriptionInfo(): ?array
    {
        $subscription = $this->activeSubscription()->with('plan')->first();
        
        if (!$subscription) {
            return null;
        }

        // Convert dates to Jalali (Persian) format
        $startDateJalali = null;
        $endDateJalali = null;
        
        if ($subscription->start_date) {
            $startDateJalali = Jalalian::fromDateTime($subscription->start_date)
                ->format('Y/m/d H:i');
        }
        
        if ($subscription->end_date) {
            $endDateJalali = Jalalian::fromDateTime($subscription->end_date)
                ->format('Y/m/d H:i');
        }

        return [
            'has_subscription' => true,
            'plan_name' => $subscription->plan->name,
            'start_date' => $subscription->start_date?->toIso8601String(),
            'start_date_jalali' => $startDateJalali,
            'end_date' => $subscription->end_date?->toIso8601String(),
            'end_date_jalali' => $endDateJalali,
            'days_remaining' => $subscription->end_date ? max(0, now()->diffInDays($subscription->end_date, false)) : 0,
        ];
    }
}
