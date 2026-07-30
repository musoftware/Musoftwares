<?php

namespace Modules\Series\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeriesVideoProgress extends Model
{
    protected $table = 'series_video_progress';

    protected $fillable = [
        'user_id',
        'series_video_id',
        'is_completed',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who owns this progress record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video this progress relates to.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(SeriesVideo::class, 'series_video_id');
    }
}
