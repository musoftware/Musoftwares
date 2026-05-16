<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\Withdrawal;
use Modules\ERP\Models\PaymentMethod;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        // Admin view
        if ($request->user()->can('manage withdrawals')) {
            $withdrawals = Withdrawal::with(['client', 'paymentMethod'])->latest()->paginate(15);
            return Inertia::render('ERP/Withdrawals/IndexAdmin', [
                'withdrawals' => $withdrawals,
            ]);
        }

        // Client view
        // Assuming the client logic is tied to the logged-in user somehow (e.g., via a client portal)
        // For now, if no permission to manage, assume client
        $client = $request->user()->client; // Assuming User has a client relation, or handled differently based on context
        if (!$client) {
            abort(403);
        }

        $withdrawals = Withdrawal::where('client_id', $client->id)
            ->with('paymentMethod')
            ->latest()
            ->paginate(15);

        $wallet = Wallet::where('owner_type', \Modules\ERP\Models\Client::class)
            ->where('owner_id', $client->id)
            ->first();

        // Calculate locked amount (pending and approved withdrawals)
        $lockedAmount = Withdrawal::where('client_id', $client->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        return Inertia::render('ERP/Withdrawals/IndexClient', [
            'withdrawals' => $withdrawals,
            'wallet' => $wallet,
            'lockedAmount' => $lockedAmount,
        ]);
    }

    public function store(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) abort(403);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        // Verify payment method belongs to client
        $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
            ->where('client_id', $client->id)
            ->where('status', 'approved')
            ->firstOrFail();

        DB::transaction(function () use ($request, $client) {
            $wallet = Wallet::where('owner_type', \Modules\ERP\Models\Client::class)
                ->where('owner_id', $client->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = $request->amount;

            // Check if available balance (balance - pending/approved withdrawals) is enough
            $lockedAmount = Withdrawal::where('client_id', $client->id)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('amount');

            if ($wallet->balance - $lockedAmount < $amount) {
                throw new \Exception('Insufficient available balance.');
            }

            Withdrawal::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $amount,
                'currency_code' => $wallet->currency,
                'status' => 'pending',
            ]);
        });

        return back()->with('success', 'Withdrawal request submitted.');
    }

    public function approve(Request $request, Withdrawal $withdrawal)
    {
        // Admin only
        $withdrawal->update(['status' => 'approved']);
        return back()->with('success', 'Withdrawal approved.');
    }

    public function markPaid(Request $request, Withdrawal $withdrawal)
    {
        // Admin only
        $request->validate([
            'reference' => 'required|string|max:255',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::transaction(function () use ($request, $withdrawal) {
            if ($withdrawal->status !== 'approved') {
                throw new \Exception('Withdrawal must be approved first.');
            }

            $wallet = Wallet::where('owner_type', \Modules\ERP\Models\Client::class)
                ->where('owner_id', $withdrawal->client_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Actually deduct from wallet
            $newBalance = $wallet->balance - $withdrawal->amount;

            $exchangeRate = 1.0;
            $businessCurrency = 'USD';
            $businessAmount = $withdrawal->amount * $exchangeRate;

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $withdrawal->amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $newBalance,
                'reference_type' => 'withdrawal',
                'reference_id' => $withdrawal->id,
                'description' => 'Withdrawal paid',
                'business_amount' => $businessAmount,
                'business_currency' => $businessCurrency,
            ]);

            $wallet->update(['balance' => $newBalance]);

            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('withdrawal_proofs', 'public');
            }

            $withdrawal->update([
                'status' => 'paid',
                'reference' => $request->reference,
                'proof_path' => $proofPath,
            ]);
        });

        return back()->with('success', 'Withdrawal marked as paid.');
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        // Admin only
        $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $withdrawal->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function cancel(Request $request, Withdrawal $withdrawal)
    {
        // Client only
        $client = $request->user()->client;
        if (!$client || $withdrawal->client_id !== $client->id) abort(403);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be canceled.');
        }

        $withdrawal->update(['status' => 'canceled']);
        return back()->with('success', 'Withdrawal canceled.');
    }
}
