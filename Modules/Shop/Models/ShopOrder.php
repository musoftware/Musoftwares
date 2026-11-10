<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShopOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    public const ANGAZNY_PENDING = 'pending';

    public const ANGAZNY_SENDING = 'sending';

    public const ANGAZNY_SENT = 'sent';

    public const ANGAZNY_FAILED = 'failed';

    public const ANGAZNY_UNKNOWN = 'unknown';

    public const ANGAZNY_SENDABLE = [self::ANGAZNY_PENDING, self::ANGAZNY_FAILED];

    protected $fillable = [
        'customer_name', 'phone', 'governorate', 'address', 'notes',
        'subtotal', 'shipping', 'total', 'status',
        'angazny_status', 'angazny_order_code', 'angazny_error', 'angazny_area_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->uuid = (string) Str::uuid();
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(ShopArea::class, 'angazny_area_id', 'angazny_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'order_id');
    }

    public function number(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
