<?php

namespace Modules\ERP\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\TenantClient;

class WalletService
{
    /**
     * Create a new wallet transaction for a client.
     */
    public function createTransaction(
        TenantClient $client,
        string $type,
        string $direction,
        $amount, // string or numeric
        int $tenantId,
        $projectId = null,
        string $note = null,
        string $referenceType = null
    ) {
        $amountStr = number_format($amount, 2, '.', '');
        
        // Ensure client has currency
        if (!$client->currency_id) {
            throw new \Exception("Client {$client->name} is missing an associated currency relation.");
        }

        $businessCurrencyId = $client->tenant->base_currency_id ?? \App\Models\AdminSettings::business_currency();

        $businessAmount = \App\Models\CurrenciesExchange::RateByDate(
            now(),
            $amountStr,
            $client->currency_id,
            $businessCurrencyId
        );

        $businessAmountStr = number_format($businessAmount, 2, '.', '');

        // For debits (sent, refunded), the amount should be stored as negative
        if ($direction === 'debit') {
            $amountStr = '-' . ltrim($amountStr, '-');
            $businessAmountStr = '-' . ltrim($businessAmountStr, '-');
        }

        return WalletTransaction::create([
            'tenant_id'            => $tenantId,
            'client_id'            => $client->id,
            'project_id'           => $projectId,
            'type'                 => $type,
            'direction'            => $direction,
            'amount'               => $amountStr,
            'currency_id'          => $client->currency_id,
            'business_amount'      => $businessAmountStr,
            'business_currency_id' => $businessCurrencyId,
            'exchange_rate'        => \App\Models\CurrenciesExchange::Rate(now()->toDateString(), $client->currency_id, $businessCurrencyId),
            'exchange_rate_date'   => now()->toDateString(),
            'reference_type'       => $referenceType,
            'reference_id'         => Auth::id(),
            'note'                 => $note,
            'created_by'           => Auth::id(),
        ]);
    }
}
