<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\Tenant;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Display a specific transaction.
     */
    public function show(Request $request, WalletTransaction $transaction)
    {
        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
            $ownerUser = $tenant?->user;
        } else {
            $tenant = Tenant::where('user_id', $user?->id)->firstOrFail();
            $ownerUser = $user;
        }

        // Verify ownership
        if ($transaction->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_transaction'));
        }

        $transaction->load(['client.currency', 'creator', 'currency']);

        $tenantCurrencyModel = \App\Models\Currency::find($tenant->base_currency_id);
        if (!$tenantCurrencyModel) {
            throw new \Exception("Tenant base currency not found.");
        }
        
        if (!$transaction->currency) {
            throw new \Exception("Transaction is missing an associated currency relation.");
        }

        $businessCurrency = $tenantCurrencyModel;

        $title = 'Manual ' . ucfirst($transaction->type);
        if ($transaction->reference_type === 'invoice') $title = 'Invoice Settlement';
        else if ($transaction->reference_type === 'withdrawal') $title = 'Withdrawal Settlement';

        $ownerDirection = $transaction->direction === 'debit' ? 'credit' : 'debit';

        $formattedTransaction = [
            'id' => $transaction->id,
            'reference_id' => '#TXN-' . str_pad($transaction->id, 4, '0', STR_PAD_LEFT),
            'title' => $title,
            'type' => $transaction->type,
            'note' => $transaction->note ?? 'No details provided',
            'direction' => strtoupper($ownerDirection),
            'amount' => round($transaction->amount, 2),
            'business_amount' => round($transaction->business_amount ?? $transaction->amount, 2),
            'currency' => $transaction->currency,
            'client_currency' => $transaction->client?->currency ?? $transaction->currency,
            'business_currency' => $businessCurrency,
            'balance_before' => 0,
            'balance_after' => 0,
            'reference_type' => $transaction->reference_type,
            'reference_id_raw' => $transaction->reference_id,
            'client_name' => $transaction->client?->name ?? 'Unknown',
            'client_id' => $transaction->client?->id,
            'authorizer' => $transaction->creator?->name ?? 'System Core',
            'date' => $transaction->created_at?->format('Y-m-d H:i'),
        ];

        return Inertia::render('ERP/Transactions/Show', [
            'transaction' => $formattedTransaction,
        ]);
    }
}
