<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A pending Kashier purchase created at checkout. Its payload (points, items, days) and amount
 * are the source of truth for fulfilment; the webhook only tells us it was paid.
 */
class KashierCheckout extends Model
{
    use SoftDeletes;

    public const PURPOSE_POINTS = 'points-purchase';

    public const PURPOSE_SUBSCRIPTION = 'subscription-purchase';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['user_id', 'purpose', 'amount', 'currency_id', 'payload', 'status', 'provider_trx_id', 'completed_at'];

    protected $casts = [
        'amount' => 'float',
        'payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public static function open(User $user, string $purpose, float $amount, int $currencyId, array $payload): self
    {
        return static::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency_id' => $currencyId,
            'payload' => $payload,
            'status' => self::STATUS_PENDING,
        ]);
    }

    public function markCompleted(string $trxId): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'provider_trx_id' => $trxId,
            'completed_at' => now(),
        ])->save();
    }
}
