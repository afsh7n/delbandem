<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Header extends Model
{
    use HasFactory;

    protected $fillable = [
        'images',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    public function getImagesUrlsAttribute()
    {
        return collect($this->images)->map(function ($image) {
            return url('storage/' . $image);
        })->toArray();
    }
}
