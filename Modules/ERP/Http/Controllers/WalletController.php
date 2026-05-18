<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Modules\Core\Services\FinancialTransactionService;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WalletController extends Controller
{
    protected FinancialTransactionService $financialService;

    public function __construct(FinancialTransactionService $financialService)
    {
        $this->financialService = $financialService;
    }

    protected function resolveClient($client)
    {
        if ($client instanceof Client) {
            return $client;
        }

        $clientModel = Client::find($client);
        if ($clientModel) {
            return $clientModel;
        }

        $user = \App\Models\User::find($client);
        $email = $user ? $user->email : 'billing@acme.corp';
        $name = $user ? $user->name : 'Acme Corp Solutions';
        $phone = ($user && !empty($user->phone)) ? $user->phone : '+1 (555) 019-2834';

        // Dynamically resolve tenant_id
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            $tenant = Tenant::first();
            if (!$tenant) {
                $owner = \App\Models\User::where('role', 'admin')->first() ?: \App\Models\User::first();
                if (!$owner) {
                    $owner = \App\Models\User::create([
                        'name' => 'System Owner',
                        'email' => 'owner@app.com',
                        'password' => bcrypt('password'),
                        'role' => 'admin',
                    ]);
                }
                $tenant = Tenant::create([
                    'user_id' => $owner->id,
                    'name' => 'Default Tenant',
                    'status' => 'active',
                ]);
            }
            $tenantId = $tenant->id;
            session(['tenant_id' => $tenantId]);
        }

        return Client::firstOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenantId,
                'name' => $name, 
                'phone' => $phone,
                'address' => '120 San Francisco, CA',
                'currency' => 'USD',
            ]
        );
    }

    public function show(Request $request, $client)
    {
        $clientModel = $this->resolveClient($client);

        $wallet = Wallet::where('owner_type', Client::class)
            ->where('owner_id', $clientModel->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => Client::class,
                'owner_id' => $clientModel->id,
                'context' => 'client',
                'balance' => 0,
                'currency' => $clientModel->currency ?? 'USD',
                'locked_balance' => 0,
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
            'client' => $clientModel,
        ]);
    }

    public function transactions(Request $request, $client)
    {
        $clientModel = $this->resolveClient($client);

        $wallet = Wallet::where('owner_type', Client::class)
            ->where('owner_id', $clientModel->id)
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

    public function manualCredit(Request $request, $client)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only super-admins can perform manual adjustments.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        $clientModel = $this->resolveClient($client);

        try {
            DB::transaction(function () use ($request, $clientModel) {
                $wallet = Wallet::firstOrCreate(
                    ['owner_type' => Client::class, 'owner_id' => $clientModel->id],
                    ['context' => 'client', 'balance' => 0, 'currency' => 'USD', 'locked_balance' => 0]
                );

                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                $amount = $request->input('amount');
                $newBalance = $wallet->balance + $amount;

                $exchangeRate = 1.0;
                $businessCurrency = 'USD';
                $businessAmount = $amount * $exchangeRate;

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $newBalance,
                    'reference_type' => 'manual_credit_audit',
                    'reference_id' => auth()->id(),
                    'description' => 'AUDIT CREDIT: ' . $request->input('note'),
                    'business_amount' => $businessAmount,
                    'business_currency' => $businessCurrency,
                ]);

                $wallet->update(['balance' => $newBalance]);
            });

            return back()->with('success', 'Wallet audited credited successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function manualDebit(Request $request, $client)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only super-admins can perform manual adjustments.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        $clientModel = $this->resolveClient($client);

        try {
            DB::transaction(function () use ($request, $clientModel) {
                $wallet = Wallet::where('owner_type', Client::class)
                    ->where('owner_id', $clientModel->id)
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
                    'reference_type' => 'manual_debit_audit',
                    'reference_id' => auth()->id(),
                    'description' => 'AUDIT DEBIT: ' . $request->input('note'),
                    'business_amount' => $businessAmount,
                    'business_currency' => $businessCurrency,
                ]);

                $wallet->update(['balance' => $newBalance]);
            });

            return back()->with('success', 'Wallet audited debited successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function lockFunds(Request $request, $client)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only super-admins can perform manual adjustments.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        $clientModel = $this->resolveClient($client);

        try {
            DB::transaction(function () use ($request, $clientModel) {
                $wallet = Wallet::where('owner_type', Client::class)
                    ->where('owner_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = $request->input('amount');

                if ($wallet->balance < $amount) {
                    throw new \Exception('Insufficient available balance to lock.');
                }

                $wallet->update([
                    'balance' => $wallet->balance - $amount,
                    'locked_balance' => ($wallet->locked_balance ?? 0) + $amount,
                ]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance + $amount,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'funds_lock_audit',
                    'reference_id' => auth()->id(),
                    'description' => 'AUDIT LOCK: ' . $request->input('note'),
                    'business_amount' => $amount,
                    'business_currency' => 'USD',
                ]);
            });
            return back()->with('success', 'Funds audited locked successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function unlockFunds(Request $request, $client)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only super-admins can perform manual adjustments.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'required|string',
        ]);

        $clientModel = $this->resolveClient($client);

        try {
            DB::transaction(function () use ($request, $clientModel) {
                $wallet = Wallet::where('owner_type', Client::class)
                    ->where('owner_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = $request->input('amount');

                if (($wallet->locked_balance ?? 0) < $amount) {
                    throw new \Exception('Insufficient locked balance to unlock.');
                }

                $wallet->update([
                    'balance' => $wallet->balance + $amount,
                    'locked_balance' => $wallet->locked_balance - $amount,
                ]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance - $amount,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'funds_unlock_audit',
                    'reference_id' => auth()->id(),
                    'description' => 'AUDIT UNLOCK: ' . $request->input('note'),
                    'business_amount' => $amount,
                    'business_currency' => 'USD',
                ]);
            });
            return back()->with('success', 'Funds audited unlocked successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function addBalance(Request $request)
    {
        $clientId = auth()->id() ?: 1;
        $clientModel = $this->resolveClient($clientId);

        $wallet = Wallet::where('owner_type', Client::class)
            ->where('owner_id', $clientModel->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => Client::class,
                'owner_id' => $clientModel->id,
                'context' => 'client',
                'balance' => 0,
                'currency' => $clientModel->currency ?? 'USD',
                'locked_balance' => 0,
            ]);
        }

        return Inertia::render('ERP/Wallet/AddBalance', [
            'wallet' => $wallet,
            'client' => $clientModel,
        ]);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
            'payment_method' => 'required|string',
        ]);

        $clientId = auth()->id() ?: 1;
        $clientModel = $this->resolveClient($clientId);

        try {
            DB::transaction(function () use ($request, $clientModel) {
                $wallet = Wallet::firstOrCreate(
                    ['owner_type' => Client::class, 'owner_id' => $clientModel->id],
                    ['context' => 'client', 'balance' => 0, 'currency' => 'USD', 'locked_balance' => 0]
                );

                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                $amount = $request->input('amount');
                $method = $request->input('payment_method');
                $newBalance = $wallet->balance + $amount;

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $newBalance,
                    'reference_type' => 'client_deposit',
                    'reference_id' => auth()->id() ?: 1,
                    'description' => "Deposit via " . ucfirst($method),
                    'business_amount' => $amount,
                    'business_currency' => 'USD',
                ]);

                $wallet->update(['balance' => $newBalance]);
            });

            return redirect()->route('erp.wallet.show', $clientModel->id)->with('success', 'Funds successfully deposited to your wallet.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
