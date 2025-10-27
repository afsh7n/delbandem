<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'sent',
    ];

    protected $casts = [
        'sent' => 'boolean',
    ];
}
