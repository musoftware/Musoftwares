<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Expense extends TenantModel
{
    protected $table = 'erp_expenses';

    protected static function booted()
    {
        parent::booted();

        static::saving(function ($expense) {
            $tenant = $expense->tenant ?? \Modules\ERP\Models\Tenant::find($expense->tenant_id);
            if (!$tenant) return;

            if (empty($expense->currency_id)) {
                $expense->currency_id = $tenant->base_currency_id;
            }

            $businessCurrencyId = $tenant->base_currency_id;
            $expense->business_currency_id = $businessCurrencyId;

            $date = $expense->date ? $expense->date->toDateString() : ($expense->created_at ? $expense->created_at->toDateString() : now()->toDateString());
            $expense->exchange_rate_date = $date;

            $expense->exchange_rate = \App\Models\CurrenciesExchange::Rate($date, $expense->currency_id, $businessCurrencyId);
            $expense->business_amount = \App\Models\CurrenciesExchange::RateByDate($date, $expense->amount, $expense->currency_id, $businessCurrencyId);
        });
    }

    protected $fillable = [
        'tenant_id', 'client_id', 'project_id', 'title', 'amount', 'category', 'date', 'description', 'created_by',
        'currency_id', 'business_amount', 'business_currency_id', 'exchange_rate', 'exchange_rate_date'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\TenantClient::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\Project::class, 'project_id');
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
