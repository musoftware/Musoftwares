<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'erp_debt_transactions';

    protected static function booted()
    {
        parent::booted();

        static::saving(function ($transaction) {
            $tenant = $transaction->tenant ?? \Modules\ERP\Models\Tenant::find($transaction->tenant_id);
            if (!$tenant) return;

            $client = $transaction->client ?? \Modules\ERP\Models\TenantClient::find($transaction->client_id);
            if (!$client) return;

            if (empty($transaction->currency_id)) {
                $transaction->currency_id = $client->currency_id ?? $tenant->base_currency_id;
            }

            $businessCurrencyId = $tenant->base_currency_id;
            $transaction->business_currency_id = $businessCurrencyId;

            $date = $transaction->date ? $transaction->date->toDateString() : ($transaction->created_at ? $transaction->created_at->toDateString() : now()->toDateString());
            $transaction->exchange_rate_date = $date;

            $transaction->exchange_rate = \App\Models\CurrenciesExchange::Rate($date, $transaction->currency_id, $businessCurrencyId);
            $transaction->business_amount = \App\Models\CurrenciesExchange::RateByDate($date, $transaction->amount, $transaction->currency_id, $businessCurrencyId);
        });
    }

    protected $fillable = [
        'tenant_id',
        'client_id',
        'type',
        'amount',
        'note',
        'date',
        'currency_id',
        'business_amount',
        'business_currency_id',
        'exchange_rate',
        'exchange_rate_date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function businessCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'business_currency_id');
    }
}
