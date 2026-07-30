<?php

namespace Modules\Series\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeriesPlaylist extends Model
{
    protected $table = 'series_playlists';

    protected $fillable = [
        'user_id',
        'youtube_playlist_id',
        'title',
        'description',
        'thumbnail',
        'channel_title',
    ];

    /**
     * Get the user that imported this series.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all videos in this series.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(SeriesVideo::class, 'series_playlist_id');
    }
}
