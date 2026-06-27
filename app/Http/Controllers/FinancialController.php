<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserReferralRequestWithdraw;
use App\Models\PayoutMethod;
use App\Models\Transaction;
use Inertia\Inertia;

class FinancialController extends Controller
{
    use \App\Traits\ConvertsCurrency;

    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = [
            'id' => null, 
            'balance' => (float)$user->user_balance, 
            'locked_balance' => (float)$user->locked_balance(),
            'currency' => $user->currency_name(),
        ];
        $transactions = $user->transactions()->latest()->paginate(15);

        $transactions->getCollection()->transform(function ($tx) {
            $balance_after = $tx->balance();
            $balance_before = $balance_after - $tx->amount;

            return [
                'id' => $tx->id,
                'type' => $tx->amount >= 0 ? 'credit' : 'debit',
                'description' => $tx->reason,
                'reference_type' => ucfirst($tx->type),
                'amount' => abs($tx->amount),
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'created_at' => $tx->created_at,
            ];
        });

        return Inertia::render('Client/Financial/Transactions', [
            'transactions' => $transactions,
            'wallet' => $wallet,
        ]);
    }

    public function withdrawals(Request $request)
    {
        $user = $request->user();
        $wallet = [
            'id' => null, 
            'balance' => (float)$user->user_balance, 
            'locked_balance' => (float)$user->locked_balance(),
            'currency' => $user->currency_name(),
        ];
        $payoutMethods = $user->payoutMethods()->where('status', 'approved')->get();
        $withdrawals = $user->withdraw()->with('payoutMethod')->latest()->paginate(15);

        $withdrawals->getCollection()->transform(function ($wd) use ($user) {
            return $wd;
        });

        return Inertia::render('Client/Financial/Withdrawals', [
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
        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $user->currency_name()];

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

        try {
            $balanceService->processWithdrawalRequest($user, (float) $request->amount, (int) $request->payout_method_id);
            return back()->with('success', __('general.withdrawal_requested_successfully'));
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => 'An error occurred while processing your withdrawal request.']);
        }
    }

    public function addBalance(Request $request)
    {
        $user = $request->user();
        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $user->currency_name()];

        $baseUSD = [50, 100, 150, 200, 400, 700, 1000];
        $presets = [];
        foreach ($baseUSD as $usd) {
            $exchanged = \App\Models\CurrenciesExchange::RateToday($usd, 1, $user->currency);
            $presets[] = \App\Helpers\FinanceHelper::instance()->price_fixer($exchanged, $user->currency);
        }

        return Inertia::render('Client/Financial/AddBalance', [
            'wallet' => $wallet,
            'presets' => $presets,
        ]);
    }

    public function depositKashier(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
        ]);

        $user = $request->user();
        $wallet = ['id' => null, 'balance' => (float)$user->user_balance, 'currency' => $user->currency_name()];

        $paymentUrl = \App\Helpers\KashierHelper::buildBalancePaymentUrl(
            (float) $request->amount,
            $user->id,
            $user->name,
            $user->email,
            $wallet['currency']
        );

        return Inertia::location($paymentUrl);
    }

    public function success(Request $request)
    {
        return redirect()->route('financial.transactions')->with('success', __('general.your_deposit_was_successful_and_has_been_credited_to_your_wallet_balance'));
    }

    public function failure(Request $request)
    {
        return redirect()->route('financial.add-balance')->with('error', __('general.payment_failed_or_was_canceled_please_try_again'));
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
                        $amountPaid = \App\Helpers\KashierHelper::getWebhookAmountInUserCurrency($amountPaid, $metadata, $user);

                        $balanceService = app(\App\Services\BalanceService::class);
                        $result = $balanceService->processKashierDepositWebhook($user, $amountPaid, $trxId);

                        if (!$result['already_processed']) {
                            \Illuminate\Support\Facades\Log::info("Kashier deposit processed successfully for User $userId, Amount: $amountPaid");
                            return response()->json(['status' => 'success', 'message' => $result['message']]);
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Duplicate Kashier webhook received for Trx $trxId - skipped");
                            return response()->json(['status' => 'success', 'message' => $result['message']]);
                        }
                    }
                }
            }
            return response()->json(['status' => 'ignored']);
        }

        return response()->json(['error' => 'Invalid webhook signature'], 400);
    }
}
