<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\User;

/**
 * @property int $id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int $package_id
 * @property float $amount
 * @property float|null $commission_amount
 * @property int|null $currency_id
 * @property string|null $notes
 * @property \Modules\Marketplace\Enums\ServiceOrderStatus|string $status
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Currency|null $currency
 * @property-read \App\Models\Conversation|null $conversation
 * @property-read MarketplaceEscrow|null $escrow
 */
class ServiceOrder extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_orders';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'package_id',
        'amount',
        'currency_id',
        'business_amount',
        'business_currency_id',
        'commission_amount',
        'status',
        'delivered_at',
        'completed_at',
        'delivery_payload',
        'auto_complete_at',
        'is_late',
        'revision_count',
        'extension_count',
        'delivery_count',
        'auto_completed',
        'requirements_completed',
        'has_dispute',
        'cancel_requested_by',
        'due_date',
        'snapshot'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'auto_complete_at' => 'datetime',
        'due_date' => 'datetime',
        'delivery_payload' => 'array',
        'snapshot' => 'array',
        'is_late' => 'boolean',
        'revision_count' => 'integer',
        'extension_count' => 'integer',
        'delivery_count' => 'integer',
        'auto_completed' => 'boolean',
        'requirements_completed' => 'boolean',
        'has_dispute' => 'boolean',
        'status' => \Modules\Marketplace\Enums\ServiceOrderStatus::class,
    ];

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(MarketplaceAttachment::class, 'attachable');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Service::class,
            ServicePackage::class,
            'id',
            'id',
            'package_id',
            'service_id'
        );
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(MarketplaceEscrow::class, 'order_id');
    }

    public function deliveryFiles(): HasMany
    {
        $foreignKey = \Illuminate\Support\Facades\Schema::hasColumn('order_delivery_files', 'order_id') ? 'order_id' : 'service_order_id';
        return $this->hasMany(OrderDeliveryFile::class, $foreignKey);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ServiceReview::class, 'order_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderRevision::class, 'order_id');
    }

    public function conversation(): MorphOne
    {
        return $this->morphOne(\App\Models\Conversation::class, 'conversable');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function isPending(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::IN_PROGRESS ||
               $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::PROCESSING;
    }

    public function isDelivered(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::DELIVERED;
    }

    public function isRevision(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::REVISION;
    }

    public function isCompleted(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::COMPLETED ||
               $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::AUTO_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::CANCELLED ||
               $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::REFUNDED;
    }

    public function isDisputed(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ServiceOrderStatus::DISPUTED;
    }
}
