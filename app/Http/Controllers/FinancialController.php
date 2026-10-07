<?php

namespace App\Http\Controllers;

use App\Builders\KashierCheckoutBuilder;
use App\Exceptions\MissingExchangeRateException;
use App\Exceptions\WithdrawalRejectedException;
use App\Helpers\FinanceHelper;
use App\Helpers\KashierHelper;
use App\Helpers\KashierSignedAmount;
use App\Models\CurrenciesExchange;
use App\Models\User;
use App\Services\BalanceService;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FinancialController extends Controller
{
    use ConvertsCurrency;

    public function transactions(Request $request)
    {
        $user = $request->user();
        $wallet = [
            'id' => null,
            'balance' => (float) $user->user_balance,
            'locked_balance' => (float) $user->locked_balance(),
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
            'balance' => (float) $user->user_balance,
            'locked_balance' => (float) $user->locked_balance(),
            'currency' => $user->currency_name(),
        ];
        $payoutMethods = $user->payoutMethods()->where('status', 'approved')->get();
        $withdrawals = $user->withdraw()->with('payoutMethod')->latest()->paginate(15);

        $withdrawals->getCollection()->transform(function ($wd) {
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

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method_id' => 'required|exists:payout_methods,id',
        ]);

        $amount = (float) $request->amount;
        $payoutMethodId = (int) $request->payout_method_id;

        // Eligibility is checked inside the service under a user row lock.
        try {
            app(BalanceService::class)->processWithdrawalRequest($user, $amount, $payoutMethodId);

            return back()->with('success', __('general.withdrawal_requested_successfully'));
        } catch (WithdrawalRejectedException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Withdrawal request failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payout_method_id' => $payoutMethodId,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['amount' => 'An error occurred while processing your withdrawal request.']);
        }
    }

    public function addBalance(Request $request)
    {
        $user = $request->user();
        $wallet = ['id' => null, 'balance' => (float) $user->user_balance, 'currency' => $user->currency_name()];

        return Inertia::render('Client/Financial/AddBalance', [
            'wallet' => $wallet,
            'presets' => $this->depositPresets($user),
        ]);
    }

    /**
     * Suggested deposit amounts (display only). With no USD rate for the wallet currency the page
     * shows no preset buttons (the form still works) instead of failing.
     */
    private function depositPresets(User $user): array
    {
        $baseUSD = [50, 100, 150, 200, 400, 700, 1000];

        try {
            return array_map(
                fn ($usd) => FinanceHelper::instance()->price_fixer(CurrenciesExchange::RateToday($usd, 1, $user->currency), $user->currency),
                $baseUSD
            );
        } catch (MissingExchangeRateException $e) {
            Log::warning('Deposit presets hidden: missing exchange rate.', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return [];
        }
    }

    public function depositKashier(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
        ]);

        $user = $request->user();
        $wallet = ['id' => null, 'balance' => (float) $user->user_balance, 'currency' => $user->currency_name()];

        $paymentUrl = KashierCheckoutBuilder::make()
            ->forAmount((float) $request->amount, $wallet['currency'])
            ->forUser($user->id, $user->name, $user->email)
            ->withSource('balance-recharge', 'deposit_')
            ->withRoutes(
                success: route('financial.add-balance.success'),
                failure: route('financial.add-balance.failure'),
                webhook: route('financial.add-balance.webhook')
            )
            ->build();

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

    /**
     * Kashier deposit webhook (synchronous). Crediting goes through
     * BalanceService::processKashierDepositWebhook, the same path the queued handler uses.
     */
    public function webhook(Request $request)
    {
        Log::info('Kashier deposit webhook received.', ['trx' => $request->input('data.transactionId')]);

        if (! KashierHelper::validatePayload()) {
            Log::warning('Kashier deposit webhook rejected: invalid signature.');

            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        $data = (array) $request->input('data', []);
        $metadata = $data['metaData'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $trxId = $data['transactionId'] ?? null;
        $user = isset($metadata['user_id']) ? User::find($metadata['user_id']) : null;
        $paid = KashierSignedAmount::fromWebhookData($data);

        if (($data['status'] ?? null) !== 'SUCCESS' || ! $trxId || ! $user || ! $paid) {
            Log::info('Kashier deposit webhook ignored.', ['trx' => $trxId, 'status' => $data['status'] ?? null]);

            return response()->json(['status' => 'ignored']);
        }

        try {
            $result = app(BalanceService::class)->processKashierDepositWebhook($user, $paid, (string) $trxId, $data);
        } catch (\Throwable $e) {
            Log::error('Kashier deposit webhook failed.', ['trx' => $trxId, 'user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Failed to process deposit'], 500);
        }

        return response()->json(['status' => 'success', 'message' => $result['message']]);
    }
}
