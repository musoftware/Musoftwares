<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Marketplace\Models\Service;

class Favorite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'service_id',
        'favoritable_type',
        'favoritable_id',
    ];

    /**
     * Get the owning favoritable model.
     */
    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the favorite.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service that is favorited.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
