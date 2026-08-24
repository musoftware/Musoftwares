<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PartnerClient extends Model
{
    use HasFactory;

    protected $table = 'partner_clients';

    protected $fillable = [
        'client_name',
        'client_key',
        'client_secret',
        'wallet_balance',
        'pricing_model',
        'cost_per_message',
        'low_balance_threshold',
        'is_active',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:4',
        'cost_per_message' => 'decimal:4',
        'low_balance_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<PartnerCreditLease>
     */
    public function leases(): HasMany
    {
        return $this->hasMany(PartnerCreditLease::class, 'partner_client_id');
    }

    /**
     * @return HasMany<PartnerUsageLog>
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(PartnerUsageLog::class, 'partner_client_id');
    }

    /**
     * Create a new partner client credential pair.
     */
    public static function createClient(
        string $name,
        float $initialBalance = 0.0,
        float $rate = 0.01,
        string $pricingModel = 'PAYG_PER_MSG'
    ): self {
        return self::create([
            'client_name' => $name,
            'client_key' => 'pk_live_' . Str::random(32),
            'client_secret' => 'sk_live_' . Str::random(48),
            'wallet_balance' => $initialBalance,
            'pricing_model' => $pricingModel,
            'cost_per_message' => $rate,
            'is_active' => true,
        ]);
    }
}
