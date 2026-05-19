<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ServiceReview extends Model
{
    protected $table = 'service_reviews';

    protected $fillable = [
        'service_id', 'order_id', 'reviewer_id', 'seller_id',
        'rating', 'review', 'is_public', 'reviewed_at',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_public'   => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'order_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Recalculate and update the parent service's rating stats.
     * Call after create or delete.
     */
    public static function syncServiceRating(int $serviceId): void
    {
        $stats = static::where('service_id', $serviceId)
            ->where('is_public', true)
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg')
            ->first();

        Service::where('id', $serviceId)->update([
            'avg_rating'   => round($stats->avg ?? 0, 2),
            'review_count' => $stats->cnt ?? 0,
        ]);
    }

    /**
     * Star display helper.
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}
