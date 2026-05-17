<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\UserWithdrawal;
use Modules\Core\Models\PayoutMethod;
use Modules\Core\Models\WalletTransaction;
use Inertia\Inertia;

class FinancialController extends Controller
{
    public function transactions(Request $request)
    {
        $wallet = $request->user()->getWallet();
        $transactions = $wallet->transactions()->latest()->paginate(15);

        return Inertia::render('Financial/Transactions', [
            'transactions' => $transactions,
            'wallet' => $wallet,
        ]);
    }

    public function withdrawals(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getWallet();
        $payoutMethods = $user->payoutMethods()->where('status', 'approved')->get();
        $withdrawals = $user->withdrawals()->with('payoutMethod')->latest()->paginate(15);

        return Inertia::render('Financial/Withdrawals', [
            'withdrawals' => $withdrawals,
            'payoutMethods' => $payoutMethods,
            'wallet' => $wallet,
        ]);
    }

    /**
     * Request a withdrawal with proper validation.
     * Recovered from old project: PayoutController::process_withdrawal()
     * Enhanced with BalanceService validation for earned balance and minimum checks.
     */
    public function requestWithdrawal(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getWallet();

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method_id' => 'required|exists:payout_methods,id',
        ]);

        // Validate using BalanceService (recovered pattern from old BalancesHelper)
        $balanceService = app(\App\Services\BalanceService::class);
        $eligibility = $balanceService->validateWithdrawalEligibility(
            $user,
            (float) $request->amount,
            (int) $request->payout_method_id
        );

        if (!$eligibility['eligible']) {
            return back()->withErrors(['amount' => $eligibility['reason']]);
        }

        $payoutMethod = $user->payoutMethods()->where('id', $request->payout_method_id)->firstOrFail();

        try {
            DB::transaction(function () use ($request, $user, $wallet, $payoutMethod) {
                $amount = $request->amount;
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $amount;

                $wallet->balance = $balanceAfter;
                $wallet->earned_balance = max(0, ($wallet->earned_balance ?? 0) - $amount);
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_type' => 'withdrawal_request',
                    'description' => 'Withdrawal request via ' . ucwords(str_replace('_', ' ', $payoutMethod->type)),
                ]);

                UserWithdrawal::create([
                    'user_id' => $user->id,
                    'payout_method_id' => $payoutMethod->id,
                    'amount' => $amount,
                    'currency' => $wallet->currency ?? 'USD',
                    'status' => 'pending',
                ]);
            });

            return back()->with('success', 'Withdrawal requested successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => 'An error occurred while processing your withdrawal request.']);
        }
    }

    public function addBalance(Request $request)
    {
        $wallet = $request->user()->getWallet();
        return Inertia::render('Financial/AddBalance', [
            'wallet' => $wallet,
        ]);
    }

    public function depositKashier(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
        ]);

        $user = $request->user();
        $wallet = $user->getWallet();

        $paymentUrl = \App\Helpers\KashierHelper::buildBalancePaymentUrl(
            (float) $request->amount,
            $user->id,
            $user->name,
            $user->email,
            $wallet->currency ?? 'USD'
        );

        return Inertia::location($paymentUrl);
    }

    public function success(Request $request)
    {
        return redirect()->route('financial.transactions')->with('success', 'Your deposit was successful and has been credited to your wallet balance.');
    }

    public function failure(Request $request)
    {
        return redirect()->route('financial.add-balance')->with('error', 'Payment failed or was canceled. Please try again.');
    }

    public function webhook(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Kashier Webhook received:', $request->all());

        if (\App\Helpers\KashierHelper::validatePayload()) {
            if ($request->input('data.status') === 'SUCCESS') {
                $data = $request->input('data');
                $metadata = $data['metaData'] ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                $userId = $metadata['user_id'] ?? null;
                $trxId = $data['transactionId'] ?? null;
                $amountPaid = floatval($data['amount'] ?? 0);

                if ($userId && $trxId && $amountPaid > 0) {
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $wallet = $user->getWallet();

                        // Idempotency check
                        $alreadyProcessed = WalletTransaction::where('reference_type', 'kashier_deposit')
                            ->where('reference_id', $trxId)
                            ->exists();

                        if (!$alreadyProcessed) {
                            DB::transaction(function () use ($wallet, $amountPaid, $trxId) {
                                $balanceBefore = $wallet->balance;
                                $balanceAfter = $balanceBefore + $amountPaid;

                                $wallet->balance = $balanceAfter;
                                $wallet->save();

                                WalletTransaction::create([
                                    'wallet_id' => $wallet->id,
                                    'type' => 'credit',
                                    'amount' => $amountPaid,
                                    'balance_before' => $balanceBefore,
                                    'balance_after' => $balanceAfter,
                                    'reference_type' => 'kashier_deposit',
                                    'reference_id' => $trxId,
                                    'description' => "Deposit via Kashier online payment (Trx: $trxId)",
                                ]);
                            });

                            \Illuminate\Support\Facades\Log::info("Kashier deposit processed successfully for User $userId, Amount: $amountPaid");
                            return response()->json(['status' => 'success', 'message' => 'Deposit processed successfully']);
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Duplicate Kashier webhook received for Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => 'Already processed']);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }
}
