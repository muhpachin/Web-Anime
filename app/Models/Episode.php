<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Episode extends Model
{
    protected $fillable = [
        'anime_id',
        'episode_number',
        'title',
        'slug',
        'created_by',
        'description',
    ];

    protected $casts = [
        'episode_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the anime that this episode belongs to.
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Get the user who created the episode from the admin panel.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the video servers for this episode.
     */
    public function videoServers(): HasMany
    {
        return $this->hasMany(VideoServer::class);
    }

    /**
     * Performance logs tied to this episode.
     */
    public function adminEpisodeLogs(): HasMany
    {
        return $this->hasMany(AdminEpisodeLog::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Episode $episode) {
            if (empty($episode->created_by) && Auth::check()) {
                $episode->created_by = Auth::id();
            }
        });
        
        static::deleting(function (Episode $episode) {
            $episode->videoServers()->delete();
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
