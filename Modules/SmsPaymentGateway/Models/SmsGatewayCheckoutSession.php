<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SmsGatewayCheckoutSession extends Model
{
    use HasFactory;

    protected $table = 'sms_gateway_checkout_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'api_key_id',
        'amount',
        'currency_id',
        'status',
        'success_url',
        'cancel_url',
        'webhook_url',
        'customer_name',
        'customer_email',
        'customer_phone',
        'metadata',
        'payment_method_types',
        'transaction_id',
        'transaction_reference',
        'is_test',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'payment_method_types' => 'array',
        'is_test' => 'boolean',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ─── Boot ──────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->session_id)) {
                $prefix = $model->is_test ? 'cs_test_' : 'cs_live_';
                $model->session_id = $prefix . Str::random(24);
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addMinutes(30);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(SmsGatewayApiKey::class, 'api_key_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentGatewayTransaction::class, 'transaction_id');
    }

    // ─── Status Helpers ────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'open' && !$this->isExpired();
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark session as complete with the matching transaction.
     */
    public function markComplete(int $transactionId, ?string $reference = null): void
    {
        $this->update([
            'status' => 'complete',
            'transaction_id' => $transactionId,
            'transaction_reference' => $reference,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark session as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    // ─── URL Helpers ───────────────────────────────────

    /**
     * Get the public checkout URL for this session.
     */
    public function getCheckoutUrl(): string
    {
        return url('/sms-pay/' . $this->session_id);
    }

    /**
     * Build the success redirect URL, replacing {SESSION_ID} placeholder.
     */
    public function getSuccessRedirectUrl(): string
    {
        return str_replace('{SESSION_ID}', $this->session_id, $this->success_url);
    }

    /**
     * Build the cancel redirect URL.
     */
    public function getCancelRedirectUrl(): ?string
    {
        return $this->cancel_url;
    }

    // ─── Scopes ────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeBySessionId($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // ─── Serialization ─────────────────────────────────

    /**
     * Convert to the API response format.
     */
    public function toApiResponse(bool $includeUrl = true): array
    {
        $data = [
            'id' => $this->session_id,
            'object' => 'checkout.session',
            'amount' => (float) $this->amount,
            'currency' => $this->currency ? $this->currency->code : null,
            'status' => $this->isExpired() && $this->status === 'open' ? 'expired' : $this->status,
            'success_url' => $this->success_url,
            'cancel_url' => $this->cancel_url,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'metadata' => $this->metadata ?? new \stdClass(),
            'payment_method_types' => $this->payment_method_types ?? ['vodafone_cash', 'instapay'],
            'is_test' => $this->is_test,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];

        if ($includeUrl) {
            $data['url'] = $this->getCheckoutUrl();
        }

        if ($this->isComplete() && $this->transaction_reference) {
            $data['transaction_reference'] = $this->transaction_reference;
        }

        return $data;
    }
}
