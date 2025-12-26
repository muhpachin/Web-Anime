<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anime extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'poster_image',
        'type',
        'status',
        'release_year',
        'rating',
        'featured',
    ];

    protected $casts = [
        'release_year' => 'integer',
        'rating' => 'float',
        'featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the genres that belong to this anime.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'anime_genre');
    }

    /**
     * Get the episodes for this anime.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number', 'asc');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($anime) {
            $anime->episodes()->delete();
            $anime->genres()->detach();
        });
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
