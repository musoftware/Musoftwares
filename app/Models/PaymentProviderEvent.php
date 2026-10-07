<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Idempotency ledger for payment provider callbacks.
 * UNIQUE(provider, external_id) guarantees one event is applied at most once.
 */
class PaymentProviderEvent extends Model
{
    use SoftDeletes;

    public const PROVIDER_KASHIER = 'kashier';

    protected $fillable = ['provider', 'external_id', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Record the event. Returns false when it was already recorded (duplicate delivery).
     * Call this inside the same DB transaction that applies the money movement.
     */
    public static function recordOnce(string $provider, string $externalId, array $payload = []): bool
    {
        try {
            // Nested transaction = savepoint, so a duplicate does not poison the caller's transaction.
            DB::transaction(fn () => static::create([
                'provider' => $provider,
                'external_id' => $externalId,
                'payload' => $payload,
            ]));
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
