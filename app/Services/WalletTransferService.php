<?php

namespace App\Services;

use App\Models\User;
use Modules\Core\Models\WalletTransfer;
use Modules\Core\Services\WalletService;
use Modules\Core\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

class WalletTransferService
{
    protected WalletService $walletService;
    protected ExchangeRateService $exchangeRateService;

    public function __construct(WalletService $walletService, ExchangeRateService $exchangeRateService)
    {
        $this->walletService = $walletService;
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Create and process a peer-to-peer wallet transfer.
     */
    public function executeTransfer(int $senderId, int $receiverId, float $amount, string $currency, ?string $reason = null): WalletTransfer
    {
        // 1. Basic validations
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

        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        $senderWallet = $sender->getWallet();
        $receiverWallet = $receiver->getWallet();

        // 2. Fetch exchange rate & calculate cross-currency conversion
        $senderCurrency = $senderWallet->currency;
        $receiverCurrency = $receiverWallet->currency;

        // If sender tries to send a currency different than their wallet currency,
        // we first validate and then handle it based on their wallet's preferred base currency.
        if ($currency !== $senderCurrency) {
            throw ValidationException::withMessages([
                'currency' => ['You can only send transfers using your wallet currency (' . $senderCurrency . ').'],
            ]);
        }

        // Calculate P2P Fee: 1% of amount, with limits translated from USD equivalents ($0.50 min, $10.00 max)
        $usdToSenderRate = (float) $this->exchangeRateService->getRate('USD', $senderCurrency);
        $minFee = 0.50 * $usdToSenderRate;
        $maxFee = 10.00 * $usdToSenderRate;

        $fee = $amount * 0.01;
        $fee = max($fee, $minFee);
        $fee = min($fee, $maxFee);
        $fee = round($fee, 2);

        $totalDebitRequired = $amount + $fee;

        // Verify Sender balance
        $senderBalance = (float) $senderWallet->balance;
        if ($senderBalance < $totalDebitRequired) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient funds. You need ' . number_format($totalDebitRequired, 2) . ' ' . $senderCurrency . ' (including fees) but only have ' . number_format($senderBalance, 2) . ' ' . $senderCurrency . '.'],
            ]);
        }

        // Calculate Rolling Security Limits ($50k USD daily, $500k USD monthly) in Sender's currency
        $dailyLimitUSD = 50000.00;
        $monthlyLimitUSD = 500000.00;

        $dailyLimitSenderCurrency = $dailyLimitUSD * $usdToSenderRate;
        $monthlyLimitSenderCurrency = $monthlyLimitUSD * $usdToSenderRate;

        // Query today's completed transfers
        $dailyTotal = WalletTransfer::where('sender_id', $senderId)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->sum('amount');

        if (($dailyTotal + $amount) > $dailyLimitSenderCurrency) {
            throw ValidationException::withMessages([
                'amount' => ['Daily transfer limit exceeded. Remaining daily limit: ' . number_format($dailyLimitSenderCurrency - $dailyTotal, 2) . ' ' . $senderCurrency . '.'],
            ]);
        }

        // Query this month's completed transfers
        $monthlyTotal = WalletTransfer::where('sender_id', $senderId)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        if (($monthlyTotal + $amount) > $monthlyLimitSenderCurrency) {
            throw ValidationException::withMessages([
                'amount' => ['Monthly transfer limit exceeded. Remaining monthly limit: ' . number_format($monthlyLimitSenderCurrency - $monthlyTotal, 2) . ' ' . $senderCurrency . '.'],
            ]);
        }

        // Retrieve exchange conversion rate
        $rawExchangeRate = (float) $this->exchangeRateService->getRate($senderCurrency, $receiverCurrency);
        
        // Apply 1.5% safe currency exchange rate margin on P2P transfers if cross-currency to protect ledger
        $finalExchangeRate = $rawExchangeRate;
        if ($senderCurrency !== $receiverCurrency) {
            $finalExchangeRate = $rawExchangeRate * (1.0 - 0.015);
        }

        $convertedAmount = round($amount * $finalExchangeRate, 2);

        try {
            return DB::transaction(function () use ($sender, $receiver, $senderWallet, $receiverWallet, $amount, $fee, $convertedAmount, $senderCurrency, $receiverCurrency, $finalExchangeRate, $reason) {
                
                // Create pending transfer record first to bind reference IDs
                $transfer = WalletTransfer::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'amount' => $amount,
                    'currency' => $senderCurrency,
                    'fee_amount' => $fee,
                    'exchange_rate' => $finalExchangeRate,
                    'converted_amount' => $convertedAmount,
                    'converted_currency' => $receiverCurrency,
                    'reason' => $reason,
                    'status' => WalletTransfer::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);

                // Debit Principal from Sender wallet
                $this->walletService->debitAvailable(
                    $senderWallet,
                    $amount,
                    $senderCurrency,
                    'p2p_transfer_sent',
                    (string) $transfer->id,
                    "P2P transfer to " . $receiver->name
                );

                // Debit Fee from Sender wallet (if any)
                if ($fee > 0) {
                    $this->walletService->debitAvailable(
                        $senderWallet,
                        $fee,
                        $senderCurrency,
                        'p2p_transfer_fee',
                        (string) $transfer->id,
                        "Transfer fee for P2P transaction to " . $receiver->name
                    );
                }

                // Credit Converted Principal to Receiver wallet
                $this->walletService->creditAvailable(
                    $receiverWallet,
                    $convertedAmount,
                    $receiverCurrency,
                    'p2p_transfer_received',
                    (string) $transfer->id,
                    "P2P transfer from " . $sender->name
                );

                Log::info("Wallet P2P Transfer completed successfully.", [
                    'transfer_id' => $transfer->id,
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'sent' => $amount . ' ' . $senderCurrency,
                    'fee' => $fee . ' ' . $senderCurrency,
                    'received' => $convertedAmount . ' ' . $receiverCurrency,
                ]);

                return $transfer;
            });

        } catch (Exception $e) {
            Log::error("Wallet P2P Transfer transaction failed.", [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            throw new Exception("The transfer transaction failed due to system error: " . $e->getMessage());
        }
    }

    /**
     * Preview transfer metrics (fee, conversion, limits).
     */
    public function previewTransfer(int $senderId, int $receiverId, float $amount, string $currency): array
    {
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        $senderWallet = $sender->getWallet();
        $receiverWallet = $receiver->getWallet();

        $senderCurrency = $senderWallet->currency;
        $receiverCurrency = $receiverWallet->currency;

        $usdToSenderRate = (float) $this->exchangeRateService->getRate('USD', $senderCurrency);
        
        // Fee preview
        $minFee = 0.50 * $usdToSenderRate;
        $maxFee = 10.00 * $usdToSenderRate;

        $fee = $amount * 0.01;
        $fee = max($fee, $minFee);
        $fee = min($fee, $maxFee);
        $fee = round($fee, 2);

        // Conversion rate preview
        $rawExchangeRate = (float) $this->exchangeRateService->getRate($senderCurrency, $receiverCurrency);
        $finalExchangeRate = $rawExchangeRate;
        if ($senderCurrency !== $receiverCurrency) {
            $finalExchangeRate = $rawExchangeRate * (1.0 - 0.015);
        }

        $convertedAmount = round($amount * $finalExchangeRate, 2);

        // Limits check
        $dailyLimitUSD = 50000.00;
        $dailyLimit = $dailyLimitUSD * $usdToSenderRate;

        $dailyTotal = WalletTransfer::where('sender_id', $senderId)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->sum('amount');

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
