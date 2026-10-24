<?php

namespace Modules\SoftwareStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $software_product_id
 * @property string $client_name
 * @property string $client_email
 * @property string $client_whatsapp
 * @property string|null $device_id
 * @property string|null $plan_key
 * @property string|null $plan_name
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property string|null $locale
 * @property \Illuminate\Support\Carbon|null $license_expires_at
 * @property string $currency
 * @property string $status
 * @property string|null $payment_gateway
 * @property string|null $payment_reference
 */
class SoftwareOrder extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending_payment';

    public const STATUS_PAID = 'paid';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'activated_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'license_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->uuid ??= (string) Str::uuid();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function reference(): string
    {
        return 'SW-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
