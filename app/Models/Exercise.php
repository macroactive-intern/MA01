<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'muscle_group',
        'equipment_type',
        'video_url',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Exercise $exercise): void {
            $exercise->slug = static::generateUniqueSlug($exercise->name);
        });
    }

    private static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
