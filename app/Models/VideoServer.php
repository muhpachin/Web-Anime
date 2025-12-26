<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoServer extends Model
{
    protected $table = 'video_servers';

    protected $fillable = [
        'episode_id',
        'server_name',
        'embed_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the episode that this video server belongs to.
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
