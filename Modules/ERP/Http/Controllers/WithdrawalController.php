<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Withdrawal;
use Modules\ERP\Models\PaymentMethod;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Inertia\Inertia;

/**
 * ERP Withdrawal Controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FINANCIAL ISOLATION BOUNDARY                                    ║
 * ║                                                                  ║
 * ║  Withdrawals here represent a tenant paying out real money to    ║
 * ║  their clients (e.g. referral commissions, refunds).             ║
 * ║                                                                  ║
 * ║  This ONLY touches ERP models:                                   ║
 * ║    • Modules\ERP\Models\ClientWallet      (client_wallets)       ║
 * ║    • Modules\ERP\Models\WalletTransaction (client_wallet_txns)   ║
 * ║    • Modules\ERP\Models\Withdrawal        (withdrawals)          ║
 * ║                                                                  ║
 * ║  It NEVER touches Core\Wallet or Core\WalletTransaction.        ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * Access model:
 *   - Tenant (admin of their workspace) → sees all withdrawals for their clients
 *   - The ERP is a tenant tool; there is no separate "client portal" role here
 */
class WithdrawalController extends Controller
{
    // ── Tenant resolution helper ─────────────────────────────────────

    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    // ── Index — tenant sees all withdrawal requests ───────────────────

    public function index(Request $request)
    {
        $tenant      = $this->resolveTenant();
        $withdrawals = Withdrawal::with(['client', 'paymentMethod'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('ERP/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
        ]);
    }

    // ── Store — tenant creates a withdrawal on behalf of a client ────

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();

        $request->validate([
            'client_id'         => 'required|exists:tenant_clients,id',
            'amount'            => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        // Verify client belongs to this tenant
        $client = TenantClient::where('tenant_id', $tenant->id)
            ->findOrFail($request->client_id);

        // Verify payment method belongs to this client and is approved
        $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
            ->where('client_id', $client->id)
            ->where('status', 'approved')
            ->firstOrFail();

        try {
            DB::transaction(function () use ($request, $tenant, $client) {
                $wallet = ClientWallet::where('tenant_id', $tenant->id)
                    ->where('client_id', $client->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $request->amount;

                // Check available balance (subtract already-pending withdrawals)
                $pendingLocked = Withdrawal::where('tenant_id', $tenant->id)
                    ->where('client_id', $client->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->sum('amount');

                $available = (float) $wallet->balance - (float) $pendingLocked;

                if ($available < $amount) {
                    throw new \Exception("Insufficient available balance. Available: {$available}, Requested: {$amount}");
                }

                Withdrawal::create([
                    'tenant_id'         => $tenant->id,
                    'client_id'         => $client->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount'            => $amount,
                    'currency_code'     => $wallet->currency,
                    'status'            => 'pending',
                    'balance_at_request'=> $wallet->balance,
                ]);
            });

            return back()->with('success', 'Withdrawal request created.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Approve ───────────────────────────────────────────────────────

    public function approve(Request $request, Withdrawal $withdrawal)
    {
        $this->authorizeTenantWithdrawal($withdrawal);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending withdrawals can be approved.']);
        }

        $withdrawal->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Withdrawal approved.');
    }

    // ── Mark Paid — deducts from ERP ClientWallet ────────────────────

    public function markPaid(Request $request, Withdrawal $withdrawal)
    {
        $this->authorizeTenantWithdrawal($withdrawal);

        $request->validate([
            'reference' => 'required|string|max:255',
            'proof'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($withdrawal->status !== 'approved') {
            return back()->withErrors(['status' => 'Withdrawal must be approved before marking as paid.']);
        }

        try {
            DB::transaction(function () use ($request, $withdrawal) {
                // Lock and deduct from ERP ClientWallet (NOT platform wallet)
                $wallet = ClientWallet::where('tenant_id', $withdrawal->tenant_id)
                    ->where('client_id', $withdrawal->client_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount    = (float) $withdrawal->amount;
                $balBefore = (float) $wallet->balance;
                $balAfter  = $balBefore - $amount;

                if ($balBefore < $amount) {
                    throw new \Exception('Client wallet balance is insufficient to complete this withdrawal.');
                }

                // Create an immutable ledger entry in ERP wallet transactions
                ClientWalletTransaction::create([
                    'tenant_id'         => $withdrawal->tenant_id,
                    'wallet_id'         => $wallet->id,
                    'type'              => 'manual_debit',
                    'direction'         => 'debit',
                    'amount'            => $amount,
                    'amount_currency'   => $wallet->currency,
                    'business_amount'   => $amount,
                    'business_currency' => $wallet->currency,
                    'exchange_rate'     => 1.0,
                    'exchange_rate_date'=> now()->toDateString(),
                    'balance_before'    => $balBefore,
                    'balance_after'     => $balAfter,
                    'reference_type'    => Withdrawal::class,
                    'reference_id'      => $withdrawal->id,
                    'note'              => 'Withdrawal paid — ref: ' . $request->reference,
                    'created_by'        => Auth::id(),
                ]);

                $wallet->update(['balance' => $balAfter]);

                // Handle proof upload
                $proofPath = null;
                if ($request->hasFile('proof')) {
                    $proofPath = $request->file('proof')->store('withdrawal_proofs', 'public');
                }

                $withdrawal->update([
                    'status'      => 'paid',
                    'reference'   => $request->reference,
                    'proof_path'  => $proofPath,
                    'paid_by'     => Auth::id(),
                    'paid_at'     => now(),
                ]);
            });

            return back()->with('success', 'Withdrawal marked as paid and client balance deducted.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Reject ────────────────────────────────────────────────────────

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $this->authorizeTenantWithdrawal($withdrawal);

        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        if (!in_array($withdrawal->status, ['pending', 'approved'])) {
            return back()->withErrors(['status' => 'This withdrawal cannot be rejected in its current state.']);
        }

        $withdrawal->update([
            'status'       => 'rejected',
            'admin_notes'  => $request->admin_notes,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
        ]);

        return back()->with('success', 'Withdrawal rejected.');
    }

    // ── Cancel ────────────────────────────────────────────────────────

    public function cancel(Request $request, Withdrawal $withdrawal)
    {
        $this->authorizeTenantWithdrawal($withdrawal);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending withdrawals can be cancelled.']);
        }

        $withdrawal->update(['status' => 'cancelled']);

        return back()->with('success', 'Withdrawal cancelled.');
    }

    // ── Auth guard helper ─────────────────────────────────────────────

    /**
     * Ensure the withdrawal belongs to the current user's tenant.
     * Aborts 403 if not.
     */
    private function authorizeTenantWithdrawal(Withdrawal $withdrawal): void
    {
        $tenant = $this->resolveTenant();
        if ($withdrawal->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this withdrawal.');
        }
    }
}
