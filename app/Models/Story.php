<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'description',
        'voice_file_name',
        'image_file_name',
        'total_rates',
        'category_id',
        'rate',
    ];

    protected $appends = [
        'voice_file_url',
        'image_file_url',
    ];

    protected function casts(): array
    {
        return [
            'total_rates' => 'integer',
            'rate' => 'float',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_favorites', 'story_id', 'user_id');
    }

    public function getVoiceFileUrlAttribute()
    {
        return $this->voice_file_name ? url('storage/' . $this->voice_file_name) : null;
    }

    public function getImageFileUrlAttribute()
    {
        return $this->image_file_name ? url('storage/' . $this->image_file_name) : null;
    }
}
