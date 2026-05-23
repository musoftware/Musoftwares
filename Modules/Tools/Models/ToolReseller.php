<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class ToolReseller extends Model
{
    protected $fillable = [
        'user_id', 'name', 'token', 'balance', 'currency_id', 'status', 'notes',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    public static array $statuses = ['active', 'suspended', 'inactive'];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function resellerUsers(): HasMany
    {
        return $this->hasMany(ToolResellerUser::class, 'reseller_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ToolResellerTransaction::class, 'reseller_id');
    }

    // ─── Status Checks ────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function activeUserCount(): int
    {
        return $this->resellerUsers()->where('status', 'active')->count();
    }

    // ─── Token Generator ─────────────────────────────────────────────────────

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    // ─── Balance Operations ──────────────────────────────────────────────────

    /**
     * Credit balance (admin top-up or manual credit).
     */
    public function creditBalance(float $amount, string $description = 'Top-up', ?int $userId = null, string $type = 'top_up'): ToolResellerTransaction
    {
        return DB::transaction(function () use ($amount, $description, $userId, $type) {
            $this->increment('balance', $amount);
            $this->refresh();

            // Re-activate if was suspended and balance is now positive
            if ($this->status === 'suspended' && $this->balance > 0) {
                $this->update(['status' => 'active']);
                $this->resellerUsers()->where('status', 'suspended_by_reseller')->update(['status' => 'active']);
            }

            return ToolResellerTransaction::create([
                'reseller_id'   => $this->id,
                'user_id'       => $userId,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $this->balance,
                'currency_id'   => $this->currency_id,
                'description'   => $description,
            ]);
        });
    }

    /**
     * Deduct balance (tool subscription charge).
     * Returns false if insufficient balance.
     */
    public function deductBalance(float $amount, string $description, ?int $userId = null, string $reference = null, string $type = 'charge'): bool
    {
        return DB::transaction(function () use ($amount, $description, $userId, $reference, $type) {
            $this->refresh();

            if ($this->balance < $amount) {
                return false;
            }

            $this->decrement('balance', $amount);
            $this->refresh();

            ToolResellerTransaction::create([
                'reseller_id'   => $this->id,
                'user_id'       => $userId,
                'type'          => $type,
                'amount'        => -$amount,
                'balance_after' => $this->balance,
                'currency_id'   => $this->currency_id,
                'description'   => $description,
                'reference'     => $reference,
            ]);

            // Auto-suspend if balance has reached zero
            if ($this->balance <= 0) {
                $this->suspend(auto: true);
            }

            return true;
        });
    }

    /**
     * Suspend the reseller and all their sub-users.
     */
    public function suspend(bool $auto = false): void
    {
        $this->update(['status' => 'suspended']);

        // Mark sub-users as suspended-by-reseller so we can distinguish from manually suspended users
        $this->resellerUsers()->where('status', 'active')->update(['status' => 'suspended_by_reseller']);

        ToolResellerTransaction::create([
            'reseller_id'   => $this->id,
            'user_id'       => null,
            'type'          => 'suspension',
            'amount'        => 0,
            'balance_after' => $this->balance,
            'currency_id'   => $this->currency_id,
            'description'   => $auto ? 'Service auto-suspended: balance reached zero.' : 'Service manually suspended by admin.',
        ]);
    }

    /**
     * Re-activate the reseller.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
        $this->resellerUsers()->where('status', 'suspended_by_reseller')->update(['status' => 'active']);
    }
}
