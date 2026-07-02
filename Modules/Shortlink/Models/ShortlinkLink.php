<?php

namespace Modules\Shortlink\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $short_code
 * @property string $destination_url
 * @property string|null $label
 * @property int|null $created_by_user_id
 * @property bool $is_active
 * @property int $clicks
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $source_type
 * @property int|null $source_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $creator
 */
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
