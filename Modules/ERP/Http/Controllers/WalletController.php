<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Inertia\Inertia;

/**
 * ERP Client Wallet Controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FINANCIAL ISOLATION BOUNDARY                                    ║
 * ║                                                                  ║
 * ║  This controller ONLY operates on ERP-internal client wallets    ║
 * ║  (table: client_wallets / client_wallet_transactions).           ║
 * ║                                                                  ║
 * ║  It MUST NEVER touch:                                            ║
 * ║    • App\Models\Wallet          (table: wallets)        ║
 * ║    • App\Models\WalletTransaction (wallet_transactions) ║
 * ║    • App\Http\Controllers\FinancialController (real money)       ║
 * ║                                                                  ║
 * ║  ERP client wallets are tenant-managed bookkeeping ledgers,      ║
 * ║  NOT real platform money. They represent credit that the tenant  ║
 * ║  grants to their clients for invoice payment purposes only.      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
class WalletController extends Controller
{
    // ── Tenant resolution helper ─────────────────────────────────────

    /**
     * Resolve the current tenant and ensure the given client belongs to it.
     * Aborts with 403 if ownership cannot be confirmed.
     */
    private function resolveTenantAndClient(int|string $clientId): array
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $client = TenantClient::where('tenant_id', $tenant->id)
            ->findOrFail($clientId);

        return [$tenant, $client];
    }

    // ── Show wallet ──────────────────────────────────────────────────

    public function show(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = ClientWallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'client_id' => $clientModel->id],
            ['balance' => 0, 'currency_id' => $clientModel->currency_id]
        );

        $transactions = ClientWalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('ERP/Wallet/Show', [
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'client'       => $clientModel,
        ]);
    }

    // ── Adjust wallet ──────────────────────────────────────────────────

    public function adjust(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = ClientWallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'client_id' => $clientModel->id],
            ['balance' => 0, 'currency_id' => $clientModel->currency_id]
        );

        return Inertia::render('ERP/Wallet/Adjust', [
            'wallet' => $wallet,
            'client' => $clientModel,
        ]);
    }

    // ── Transaction history (JSON) ────────────────────────────────────

    public function transactions(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = ClientWallet::where('tenant_id', $tenant->id)
            ->where('client_id', $clientModel->id)
            ->firstOrFail();

        $query = ClientWalletTransaction::where('wallet_id', $wallet->id);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        return response()->json($query->latest()->paginate(20));
    }

    // ── Manual credit (tenant adds credit to client's ERP wallet) ────

    public function manualCredit(Request $request, int $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'required|string|max:500',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            DB::transaction(function () use ($request, $tenant, $clientModel) {
                $wallet = ClientWallet::where('tenant_id', $tenant->id)
                    ->where('client_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount      = (float) $request->input('amount');
                $balBefore   = (float) $wallet->balance;
                $balAfter    = $balBefore + $amount;

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'manual_credit',
                    'direction'        => 'credit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $amount,
                    'business_currency_id'=> $clientModel->currency_id,
                    'exchange_rate'    => 1.0,
                    'exchange_rate_date'=> now()->toDateString(),
                    'balance_before'   => $balBefore,
                    'balance_after'    => $balAfter,
                    'reference_type'   => 'manual_credit',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Credit: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);

                $wallet->update(['balance' => $balAfter]);
            });

            return back()->with('success', 'Client credit added successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Manual debit (tenant removes credit from client's ERP wallet) ─

    public function manualDebit(Request $request, int $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'required|string|max:500',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            DB::transaction(function () use ($request, $tenant, $clientModel) {
                $wallet = ClientWallet::where('tenant_id', $tenant->id)
                    ->where('client_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $request->input('amount');

                if ((float) $wallet->balance < $amount) {
                    throw new \Exception('Insufficient client wallet balance.');
                }

                $balBefore = (float) $wallet->balance;
                $balAfter  = $balBefore - $amount;

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'manual_debit',
                    'direction'        => 'debit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $amount,
                    'business_currency_id'=> $clientModel->currency_id,
                    'exchange_rate'    => 1.0,
                    'exchange_rate_date'=> now()->toDateString(),
                    'balance_before'   => $balBefore,
                    'balance_after'    => $balAfter,
                    'reference_type'   => 'manual_debit',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Debit: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);

                $wallet->update(['balance' => $balAfter]);
            });

            return back()->with('success', 'Client debit recorded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Lock funds (holds a portion so it can't be spent on invoices) ─

    public function lockFunds(Request $request, int $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'required|string|max:500',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            DB::transaction(function () use ($request, $tenant, $clientModel) {
                $wallet = ClientWallet::where('tenant_id', $tenant->id)
                    ->where('client_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $request->input('amount');

                if ((float) $wallet->balance < $amount) {
                    throw new \Exception('Insufficient available balance to lock.');
                }

                $balBefore   = (float) $wallet->balance;
                $lockedBefore= (float) ($wallet->locked_balance ?? 0);
                $balAfter    = $balBefore - $amount;
                $lockedAfter = $lockedBefore + $amount;

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'manual_debit',
                    'direction'        => 'debit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $amount,
                    'business_currency_id'=> $clientModel->currency_id,
                    'exchange_rate'    => 1.0,
                    'exchange_rate_date'=> now()->toDateString(),
                    'balance_before'   => $balBefore,
                    'balance_after'    => $balAfter,
                    'reference_type'   => 'funds_lock',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Funds locked: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);

                $wallet->update([
                    'balance'        => $balAfter,
                    'locked_balance' => $lockedAfter,
                ]);
            });

            return back()->with('success', 'Funds locked successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Unlock funds ──────────────────────────────────────────────────

    public function unlockFunds(Request $request, int $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'required|string|max:500',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            DB::transaction(function () use ($request, $tenant, $clientModel) {
                $wallet = ClientWallet::where('tenant_id', $tenant->id)
                    ->where('client_id', $clientModel->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $request->input('amount');

                if ((float) ($wallet->locked_balance ?? 0) < $amount) {
                    throw new \Exception('Insufficient locked balance to unlock.');
                }

                $balBefore   = (float) $wallet->balance;
                $lockedBefore= (float) $wallet->locked_balance;
                $balAfter    = $balBefore + $amount;
                $lockedAfter = $lockedBefore - $amount;

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'wallet_id'        => $wallet->id,
                    'type'             => 'manual_credit',
                    'direction'        => 'credit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $amount,
                    'business_currency_id'=> $clientModel->currency_id,
                    'exchange_rate'    => 1.0,
                    'exchange_rate_date'=> now()->toDateString(),
                    'balance_before'   => $balBefore,
                    'balance_after'    => $balAfter,
                    'reference_type'   => 'funds_unlock',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Funds unlocked: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);

                $wallet->update([
                    'balance'        => $balAfter,
                    'locked_balance' => $lockedAfter,
                ]);
            });

            return back()->with('success', 'Funds unlocked successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
