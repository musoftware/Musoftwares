<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMoneyTransfer;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class UserMoneyTransferService
{
    /**
     * Create a new money transfer
     */
    public function createTransfer($senderId, $receiverId, $amount, $currency, $reason = null)
    {
        // Check if feature is enabled
        if (!config('money_transfer.enabled', true)) {
            throw new Exception(config('money_transfer.disabled_message', 'Money transfer is disabled'));
        }

        // Validate basic parameters
        $this->validateTransferParameters($senderId, $receiverId, $amount, $currency);

        // Get users
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        // Validate users
        $this->validateUsers($sender, $receiver);

        // Validate amount
        $this->validateAmount($amount, $currency);

        // Check transfer limits
        $this->checkTransferLimits($sender, $amount, $currency);

        // Check if users are blacklisted
        $this->checkBlacklistedUsers($sender, $receiver);

        // Check supported currencies
        $this->checkSupportedCurrencies($currency);

        try {
            DB::beginTransaction();

            // Create the transfer
            $transfer = UserMoneyTransfer::createTransfer($senderId, $receiverId, $amount, $currency, $reason);

            // Process the transfer if auto-approve is enabled
            if (config('money_transfer.auto_approve', true)) {
                $this->processTransfer($transfer);
            }

            DB::commit();

            // Send notifications
            $this->sendTransferNotifications($transfer);

            // Log the transfer
            $this->logTransfer($transfer, 'created');

            return $transfer;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create money transfer', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process a pending transfer
     */
    public function processTransfer(UserMoneyTransfer $transfer)
    {
        if (!$transfer->canBeProcessed()) {
            throw new Exception('Transfer cannot be processed in current status');
        }

        try {
            DB::beginTransaction();

            // Process the transfer
            $transfer->process();

            DB::commit();

            // Send completion notifications
            $this->sendTransferCompletionNotifications($transfer);

            // Log the transfer
            $this->logTransfer($transfer, 'processed');

            return $transfer;

        } catch (Exception $e) {
            DB::rollBack();
            
            // Mark transfer as failed
            $transfer->update(['status' => UserMoneyTransfer::STATUS_FAILED]);
            
            Log::error('Failed to process money transfer', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Cancel a transfer
     */
    public function cancelTransfer(UserMoneyTransfer $transfer, $reason = null)
    {
        if (!$transfer->canBeCancelled()) {
            throw new Exception('Transfer cannot be cancelled in current status');
        }

        try {
            DB::beginTransaction();

            // Cancel the transfer
            $transfer->cancel($reason);

            DB::commit();

            // Send cancellation notifications
            $this->sendTransferCancellationNotifications($transfer);

            // Log the transfer
            $this->logTransfer($transfer, 'cancelled');

            return $transfer;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel money transfer', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get transfer history for a user
     */
    public function getUserTransferHistory($userId, $type = 'all', $limit = 20)
    {
        $query = UserMoneyTransfer::where(function ($q) use ($userId, $type) {
            if ($type === 'sent' || $type === 'all') {
                $q->orWhere('sender_id', $userId);
            }
            if ($type === 'received' || $type === 'all') {
                $q->orWhere('receiver_id', $userId);
            }
        });

        return $query->with(['sender', 'receiver', 'currencyModel', 'feeCurrencyModel'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get pending transfers for admin approval
     */
    public function getPendingTransfers($limit = 20)
    {
        return UserMoneyTransfer::pending()
            ->with(['sender', 'receiver', 'currencyModel'])
            ->orderBy('created_at', 'asc')
            ->paginate($limit);
    }

    /**
     * Calculate transfer fee
     */
    public function calculateTransferFee($amount, $currency)
    {
        $feePercentage = config('money_transfer.fee_percentage', 0.01);
        $minFee = config('money_transfer.min_fee', 0.50);
        $maxFee = config('money_transfer.max_fee', 10.00);

        $feeAmount = $amount * $feePercentage;
        $feeAmount = max($feeAmount, $minFee);
        $feeAmount = min($feeAmount, $maxFee);

        return round($feeAmount, 2);
    }

    /**
     * Calculate converted amount for cross-currency transfers
     */
    public function calculateConvertedAmount($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $exchangeRate = CurrenciesExchange::RateToday(1, $fromCurrency, $toCurrency);
        $convertedAmount = $amount * $exchangeRate;

        // Apply exchange rate margin if configured
        $margin = config('money_transfer.exchange_rate_margin', 0.02);
        $convertedAmount = $convertedAmount * (1 - $margin);

        return round($convertedAmount, 2);
    }

    /**
     * Check if user has sufficient balance for transfer
     */
    public function checkUserBalance($userId, $amount, $currency)
    {
        $user = User::findOrFail($userId);
        $fee = $this->calculateTransferFee($amount, $currency);
        $totalRequired = $amount + $fee;

        return $user->user_balance >= $totalRequired;
    }

    /**
     * Validate transfer parameters
     */
    private function validateTransferParameters($senderId, $receiverId, $amount, $currency)
    {
        if ($senderId === $receiverId) {
            throw new Exception('Cannot transfer money to yourself');
        }

        if ($amount <= 0) {
            throw new Exception('Transfer amount must be greater than zero');
        }

        if (!is_numeric($amount)) {
            throw new Exception('Invalid transfer amount');
        }

        if (!$currency) {
            throw new Exception('Currency is required');
        }
    }

    /**
     * Validate users
     */
    private function validateUsers($sender, $receiver)
    {
        if (!$sender || !$receiver) {
            throw new Exception('Invalid sender or receiver');
        }

        if ($sender->id === $receiver->id) {
            throw new Exception('Cannot transfer money to yourself');
        }
    }

    /**
     * Validate amount
     */
    private function validateAmount($amount, $currency)
    {
        $minAmount = config('money_transfer.min_amount', 0.01);
        $maxAmount = config('money_transfer.max_amount', 10000.00);

        if ($amount < $minAmount) {
            throw new Exception("Transfer amount must be at least {$minAmount}");
        }

        if ($amount > $maxAmount) {
            throw new Exception("Transfer amount cannot exceed {$maxAmount}");
        }
    }

    /**
     * Check transfer limits
     */
    private function checkTransferLimits($user, $amount, $currency)
    {
        $dailyLimit = config('money_transfer.daily_limit', 50000.00);
        $monthlyLimit = config('money_transfer.monthly_limit', 500000.00);

        // Check daily limit
        $dailyTotal = UserMoneyTransfer::where('sender_id', $user->id)
            ->where('status', UserMoneyTransfer::STATUS_COMPLETED)
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new Exception("Daily transfer limit exceeded. Daily limit: {$dailyLimit}");
        }

        // Check monthly limit
        $monthlyTotal = UserMoneyTransfer::where('sender_id', $user->id)
            ->where('status', UserMoneyTransfer::STATUS_COMPLETED)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        if (($monthlyTotal + $amount) > $monthlyLimit) {
            throw new Exception("Monthly transfer limit exceeded. Monthly limit: {$monthlyLimit}");
        }
    }

    /**
     * Check if users are blacklisted
     */
    private function checkBlacklistedUsers($sender, $receiver)
    {
        $blacklistedUsers = config('money_transfer.blacklisted_users', '');
        
        if ($blacklistedUsers) {
            $blacklistedIds = array_map('trim', explode(',', $blacklistedUsers));
            
            if (in_array($sender->id, $blacklistedIds)) {
                throw new Exception('Sender is not allowed to make transfers');
            }
            
            if (in_array($receiver->id, $blacklistedIds)) {
                throw new Exception('Receiver is not allowed to receive transfers');
            }
        }
    }

    /**
     * Check supported currencies
     */
    private function checkSupportedCurrencies($currency)
    {
        $supportedCurrencies = config('money_transfer.supported_currencies', '');
        
        if ($supportedCurrencies) {
            $supportedIds = array_map('trim', explode(',', $supportedCurrencies));
            
            if (!in_array($currency, $supportedIds)) {
                throw new Exception('Currency not supported for transfers');
            }
        }
    }

    /**
     * Send transfer notifications
     */
    private function sendTransferNotifications($transfer)
    {
        if (config('money_transfer.notify_sender', true)) {
            // Send notification to sender
            $this->sendNotification($transfer->sender, 'transfer_created', $transfer);
        }

        if (config('money_transfer.notify_receiver', true)) {
            // Send notification to receiver
            $this->sendNotification($transfer->receiver, 'transfer_received', $transfer);
        }

        if (config('money_transfer.notify_admin', false)) {
            $threshold = config('money_transfer.admin_notification_threshold', 1000.00);
            if ($transfer->amount >= $threshold) {
                $this->sendAdminNotification($transfer);
            }
        }
    }

    /**
     * Send transfer completion notifications
     */
    private function sendTransferCompletionNotifications($transfer)
    {
        if (config('money_transfer.notify_sender', true)) {
            $this->sendNotification($transfer->sender, 'transfer_completed', $transfer);
        }

        if (config('money_transfer.notify_receiver', true)) {
            $this->sendNotification($transfer->receiver, 'transfer_completed', $transfer);
        }
    }

    /**
     * Send transfer cancellation notifications
     */
    private function sendTransferCancellationNotifications($transfer)
    {
        if (config('money_transfer.notify_sender', true)) {
            $this->sendNotification($transfer->sender, 'transfer_cancelled', $transfer);
        }

        if (config('money_transfer.notify_receiver', true)) {
            $this->sendNotification($transfer->receiver, 'transfer_cancelled', $transfer);
        }
    }

    /**
     * Send notification to user
     */
    private function sendNotification($user, $type, $transfer)
    {
        // This would integrate with your notification system
        // For now, we'll just log it
        Log::info("Notification sent to user {$user->id} for transfer {$transfer->id}", [
            'type' => $type,
            'user_id' => $user->id,
            'transfer_id' => $transfer->id
        ]);
    }

    /**
     * Send admin notification
     */
    private function sendAdminNotification($transfer)
    {
        // This would integrate with your admin notification system
        Log::info("Admin notification for large transfer {$transfer->id}", [
            'transfer_id' => $transfer->id,
            'amount' => $transfer->amount,
            'currency' => $transfer->currency
        ]);
    }

    /**
     * Log transfer activity
     */
    private function logTransfer($transfer, $action)
    {
        if (!config('money_transfer.log_transfers', true)) {
            return;
        }

        $logLevel = config('money_transfer.log_level', 'info');
        
        Log::log($logLevel, "Money transfer {$action}", [
            'transfer_id' => $transfer->id,
            'sender_id' => $transfer->sender_id,
            'receiver_id' => $transfer->receiver_id,
            'amount' => $transfer->amount,
            'currency' => $transfer->currency,
            'status' => $transfer->status,
            'action' => $action
        ]);
    }

    /**
     * Clean up expired pending transfers
     */
    public function cleanupExpiredTransfers()
    {
        $expiryHours = config('money_transfer.pending_transfer_expiry_hours', 24);
        $expiryDate = Carbon::now()->subHours($expiryHours);

        $expiredTransfers = UserMoneyTransfer::pending()
            ->where('created_at', '<', $expiryDate)
            ->get();

        foreach ($expiredTransfers as $transfer) {
            try {
                $this->cancelTransfer($transfer, 'Transfer expired automatically');
            } catch (Exception $e) {
                Log::error("Failed to cancel expired transfer {$transfer->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $expiredTransfers->count();
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics($period = 'month')
    {
        $query = UserMoneyTransfer::where('status', UserMoneyTransfer::STATUS_COMPLETED);

        switch ($period) {
            case 'day':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_transfers,
            SUM(amount) as total_amount,
            SUM(fee_amount) as total_fees,
            currency
        ')
        ->groupBy('currency')
        ->get();

        return $stats;
    }
}
