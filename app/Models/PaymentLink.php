<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const METHOD_KASHIER = 'kashier';
    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'uuid',
        'user_id',
        'client_id',
        'title',
        'description',
        'amount',
        'currency_id',
        'status',
        'paid_at',
        'expires_at',
        'cancelled_at',
        'paid_method',
        'paid_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markPaid(string $method = self::METHOD_KASHIER, ?string $transactionId = null, ?int $actorUserId = null): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        $metadata = $this->metadata ?? [];
        if ($method === self::METHOD_MANUAL) {
            $metadata['marked_paid_by_user_id'] = $actorUserId;
            $metadata['marked_paid_at'] = now()->toIso8601String();
        } elseif ($transactionId) {
            $metadata['transaction_id'] = $transactionId;
        }

        $this->forceFill([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'paid_method' => $method,
            'paid_transaction_id' => $transactionId,
            'metadata' => $metadata,
        ])->save();

        return true;
    }

    public function markCancelled(): bool
    {
        if (! $this->canBeCancelled()) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();

        return true;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('client_id', $userId);
        });
    }
}