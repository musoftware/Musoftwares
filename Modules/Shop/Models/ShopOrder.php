<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShopOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    protected $fillable = [
        'customer_name', 'phone', 'governorate', 'address', 'notes',
        'subtotal', 'shipping', 'total', 'status',
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

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'order_id');
    }

    public function number(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
