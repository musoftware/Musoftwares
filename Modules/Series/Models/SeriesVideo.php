<?php

namespace Modules\Series\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeriesVideo extends Model
{
    protected $table = 'series_videos';

    protected $fillable = [
        'series_playlist_id',
        'youtube_video_id',
        'title',
        'description',
        'thumbnail',
        'position',
    ];

    /**
     * Get the playlist this video belongs to.
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(SeriesPlaylist::class, 'series_playlist_id');
    }

    /**
     * Get the progress records for this video.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(SeriesVideoProgress::class, 'series_video_id');
    }

    /**
     * Get the specific study progress for a given user.
     */
    public function progressForUser(int $userId): HasOne
    {
        return $this->hasOne(SeriesVideoProgress::class, 'series_video_id')
            ->where('user_id', $userId);
    }
}
