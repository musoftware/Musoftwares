<?php

namespace App\Services;

use App\Helpers\FinanceHelper;
use App\Models\Currency;
use App\Models\User;
use App\Models\WalletTransfer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WalletTransferService extends BaseService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    private const DAILY_LIMIT_USD = 50000.00;

    private const MONTHLY_LIMIT_USD = 500000.00;

    /**
     * Create and process a peer-to-peer wallet transfer.
     *
     * Balance and daily/monthly limits are checked inside the transaction while
     * the sender row is locked, so parallel transfers cannot overspend.
     */
    public function executeTransfer(int $senderId, int $receiverId, float $amount, int $currencyId, ?string $reason = null): WalletTransfer
    {
        $this->assertTransferRequestValid($senderId, $receiverId, $amount);

        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        if ($currencyId !== (int) $sender->currency_id) {
            throw ValidationException::withMessages([
                'currency' => ['You can only send transfers using your wallet currency.'],
            ]);
        }

        $senderCurrency = $this->currencyCodeOf($sender, 'Sender');
        $receiverCurrency = $this->currencyCodeOf($receiver, 'Receiver');

        $usdToSenderRate = (float) $this->exchangeRateService->getRate('USD', $senderCurrency);
        $finalExchangeRate = $this->transferExchangeRate($senderCurrency, $receiverCurrency);

        $quote = [
            'amount' => $amount,
            'fee' => $this->calculateFee($amount, $usdToSenderRate),
            'converted_amount' => round($amount * $finalExchangeRate, 2),
            'exchange_rate' => $finalExchangeRate,
            'sender_currency' => $senderCurrency,
            'receiver_currency' => $receiverCurrency,
            'currency_id' => $currencyId,
            'usd_to_sender_rate' => $usdToSenderRate,
        ];

        try {
            return $this->executeInTransaction(fn () => $this->settleTransfer($senderId, $receiverId, $quote, $reason));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Wallet P2P Transfer transaction failed.', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('The transfer transaction failed due to system error: '.$e->getMessage());
        }
    }

    /**
     * Must run inside a transaction. Locks both wallets (in id order to avoid
     * deadlocks), re-checks funds and limits on fresh data, then moves the money.
     */
    private function settleTransfer(int $senderId, int $receiverId, array $quote, ?string $reason): WalletTransfer
    {
        $locked = User::whereKey([$senderId, $receiverId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $sender = $locked->get($senderId);
        $receiver = $locked->get($receiverId);

        $this->assertSufficientFunds($sender, $quote['amount'] + $quote['fee'], $quote['currency_id']);
        $this->assertWithinTransferLimits($senderId, $quote['amount'], $quote['usd_to_sender_rate'], $quote['currency_id']);

        $transfer = WalletTransfer::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => $quote['amount'],
            'fee_amount' => $quote['fee'],
            'currency' => $quote['sender_currency'],
            'exchange_rate' => $quote['exchange_rate'],
            'converted_amount' => $quote['converted_amount'],
            'converted_currency' => $quote['receiver_currency'],
            'reason' => $reason,
            'status' => WalletTransfer::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        $sender->add_balance(-1 * $quote['amount'], 'P2P transfer to '.$receiver->name, 'used');
        if ($quote['fee'] > 0) {
            $sender->add_balance(-1 * $quote['fee'], 'P2P transfer fee', 'used');
        }
        $receiver->add_balance($quote['converted_amount'], 'P2P transfer from '.$sender->name, 'received');

        Log::info('Wallet P2P Transfer completed successfully.', [
            'transfer_id' => $transfer->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'sent' => $quote['amount'].' '.$quote['sender_currency'],
            'fee' => $quote['fee'].' '.$quote['sender_currency'],
            'received' => $quote['converted_amount'].' '.$quote['receiver_currency'],
        ]);

        return $transfer;
    }

    private function assertTransferRequestValid(int $senderId, int $receiverId, float $amount): void
    {
        if ($senderId === $receiverId) {
            throw ValidationException::withMessages([
                'receiver_email' => ['You cannot transfer money to yourself.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Transfer amount must be greater than zero.'],
            ]);
        }
    }

    private function currencyCodeOf(User $user, string $role): string
    {
        $currency = Currency::find($user->currency_id);
        if (! $currency) {
            throw new Exception("{$role} (User #{$user->id}) is missing a currency_id configuration.");
        }

        return $currency->currency;
    }

    /**
     * P2P fee: 1% of amount, kept between $0.50 and $10.00 (USD equivalents in sender currency).
     */
    private function calculateFee(float $amount, float $usdToSenderRate): float
    {
        $fee = max($amount * 0.01, 0.50 * $usdToSenderRate);

        return round(min($fee, 10.00 * $usdToSenderRate), 2);
    }

    /**
     * Cross-currency transfers get a 1.5% margin to protect the ledger.
     */
    private function transferExchangeRate(string $senderCurrency, string $receiverCurrency): float
    {
        $rawExchangeRate = (float) $this->exchangeRateService->getRate($senderCurrency, $receiverCurrency);

        return $senderCurrency === $receiverCurrency ? $rawExchangeRate : $rawExchangeRate * (1.0 - 0.015);
    }

    private function assertSufficientFunds(User $sender, float $totalDebitRequired, int $currencyId): void
    {
        $senderBalance = (float) $sender->available_balance();
        if ($senderBalance >= $totalDebitRequired) {
            return;
        }

        $money = FinanceHelper::instance();
        throw ValidationException::withMessages([
            'amount' => ['Insufficient funds. You need '.$money->format_money($totalDebitRequired, $currencyId).' (including fees) but only have '.$money->format_money($senderBalance, $currencyId).'.'],
        ]);
    }

    /**
     * Rolling security limits ($50k USD daily, $500k USD monthly) in sender currency.
     */
    private function assertWithinTransferLimits(int $senderId, float $amount, float $usdToSenderRate, int $currencyId): void
    {
        $periods = [
            'Daily' => [Carbon::now()->startOfDay(), self::DAILY_LIMIT_USD * $usdToSenderRate],
            'Monthly' => [Carbon::now()->startOfMonth(), self::MONTHLY_LIMIT_USD * $usdToSenderRate],
        ];

        foreach ($periods as $label => [$since, $limit]) {
            $sentInPeriod = $this->completedTransferTotalSince($senderId, $since);
            if (($sentInPeriod + $amount) <= $limit) {
                continue;
            }

            throw ValidationException::withMessages([
                'amount' => [$label.' transfer limit exceeded. Remaining '.strtolower($label).' limit: '.FinanceHelper::instance()->format_money($limit - $sentInPeriod, $currencyId).'.'],
            ]);
        }
    }

    private function completedTransferTotalSince(int $senderId, Carbon $since): float
    {
        return (float) WalletTransfer::where('sender_id', $senderId)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', $since)
            ->sum('amount');
    }

    /**
     * Preview transfer metrics (fee, conversion, limits).
     */
    public function previewTransfer(int $senderId, int $receiverId, float $amount, int $currencyId): array
    {
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        if ($currencyId !== (int) $sender->currency_id) {
            throw ValidationException::withMessages([
                'currency' => ['You can only send transfers using your wallet currency.'],
            ]);
        }

        $senderCurrency = $this->currencyCodeOf($sender, 'Sender');
        $receiverCurrency = $this->currencyCodeOf($receiver, 'Receiver');

        $usdToSenderRate = (float) $this->exchangeRateService->getRate('USD', $senderCurrency);

        $fee = $this->calculateFee($amount, $usdToSenderRate);
        $finalExchangeRate = $this->transferExchangeRate($senderCurrency, $receiverCurrency);

        $convertedAmount = round($amount * $finalExchangeRate, 2);

        $dailyLimit = self::DAILY_LIMIT_USD * $usdToSenderRate;
        $dailyTotal = $this->completedTransferTotalSince($senderId, Carbon::now()->startOfDay());

        $remainingLimit = max(0.00, $dailyLimit - $dailyTotal);

        return [
            'amount' => $amount,
            'currency' => $senderCurrency,
            'fee' => $fee,
            'exchange_rate' => $finalExchangeRate,
            'converted_amount' => $convertedAmount,
            'converted_currency' => $receiverCurrency,
            'remaining_limit' => $remainingLimit,
            'requires_conversion' => $senderCurrency !== $receiverCurrency,
        ];
    }
}
