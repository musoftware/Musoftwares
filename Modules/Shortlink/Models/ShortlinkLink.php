<?php

namespace Modules\Shortlink\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShortlinkLink extends Model
{
    use SoftDeletes;

    protected $table = 'shortlink_links';

    protected $fillable = [
        'short_code',
        'destination_url',
        'label',
        'created_by_user_id',
        'is_active',
        'clicks',
        'expires_at',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'clicks' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
