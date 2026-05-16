<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Modules\Core\Services\FinancialTransactionService;
use Modules\ERP\Models\Client;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WalletController extends Controller
{
    protected FinancialTransactionService $financialService;

    public function __construct(FinancialTransactionService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function show(Request $request, Client $client)
    {
        $wallet = Wallet::where('owner_type', Client::class)
            ->where('owner_id', $client->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => Client::class,
                'owner_id' => $client->id,
                'context' => 'client',
                'balance' => 0,
                'currency' => 'USD', // Default, should be dynamic based on requirements
            ]);
        }

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'wallet' => $wallet,
                'transactions' => $transactions,
            ]);
        }

        return Inertia::render('ERP/Wallet/Show', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'client' => $client,
        ]);
    }

    public function transactions(Request $request, Client $client)
    {
        $wallet = Wallet::where('owner_type', Client::class)
            ->where('owner_id', $client->id)
            ->firstOrFail();

        $query = WalletTransaction::where('wallet_id', $wallet->id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        $transactions = $query->latest()->paginate(15);

        return response()->json($transactions);
    }

    public function manualCredit(Request $request, Client $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $client) {
            $wallet = Wallet::firstOrCreate(
                ['owner_type' => Client::class, 'owner_id' => $client->id],
                ['context' => 'client', 'balance' => 0, 'currency' => 'USD']
            );

            // Using pessimistic locking on wallet inside transaction
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $amount = $request->input('amount');
            $newBalance = $wallet->balance + $amount;

            // Simple exchange rate mock since actual rate isn't provided here, assuming 1.0 for USD/USD
            $exchangeRate = 1.0;
            $businessCurrency = 'USD'; // Base business currency
            $businessAmount = $amount * $exchangeRate;

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $newBalance,
                'reference_type' => 'manual_credit',
                'reference_id' => auth()->id(), // Admin who did it
                'description' => $request->input('note'),
                'business_amount' => $businessAmount,
                'business_currency' => $businessCurrency,
            ]);

            $wallet->update(['balance' => $newBalance]);
        });

        return back()->with('success', 'Wallet credited successfully.');
    }

    public function manualDebit(Request $request, Client $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $client) {
            $wallet = Wallet::where('owner_type', Client::class)
                ->where('owner_id', $client->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = $request->input('amount');

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient funds.');
            }

            $newBalance = $wallet->balance - $amount;

            $exchangeRate = 1.0;
            $businessCurrency = 'USD';
            $businessAmount = $amount * $exchangeRate;

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $newBalance,
                'reference_type' => 'manual_debit',
                'reference_id' => auth()->id(), // Admin who did it
                'description' => $request->input('note'),
                'business_amount' => $businessAmount,
                'business_currency' => $businessCurrency,
            ]);

            $wallet->update(['balance' => $newBalance]);
        });

        return back()->with('success', 'Wallet debited successfully.');
    }
}
