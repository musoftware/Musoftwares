<?php

namespace App\Services;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppDailyBatch;
use App\Models\Transaction;
use App\Models\User;
use App\Helper\BalancesHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppBillingService
{
    protected $costPerMessage = 0.25; // EGP per message

    /**
     * Get cost per message in user's currency
     */
    public function getCostInUserCurrency(User $user, $costInEgp = null)
    {
        $costInEgp = $costInEgp ?? $this->costPerMessage;

        // Get user's currency exchange rate
        $exchangeRate = $this->getExchangeRate($user);

        return $costInEgp * $exchangeRate;
    }

    /**
     * Get exchange rate for user's currency
     */
    protected function getExchangeRate(User $user)
    {
        // Default to EGP if no currency set
        $userCurrency = $user->currency ?? 'EGP';

        if ($userCurrency === 'EGP') {
            return 1.0;
        }

        // Get exchange rate from database - using correct column names
        $exchangeRate = \App\Models\CurrenciesExchange::where('currency1', 'EGP')
            ->where('currency2', $userCurrency)
            ->value('rate');

        return $exchangeRate ?? 1.0;
    }

    /**
     * Get exchange rate for user's currency (public method)
     */
    public function getExchangeRateForUser(User $user)
    {
        return $this->getExchangeRate($user);
    }

    /**
     * Check and reset WhatsApp balance if needed
     */
    public function checkAndResetWhatsAppBalance(User $user)
    {
        if ($user->shouldResetWhatsAppBalance()) {
            $user->resetWhatsAppBalance(0.00); // Reset to 0 EGP
            Log::info("WhatsApp balance reset for user", [
                'user_id' => $user->id,
                'new_balance' => 0.00,
                'reset_date' => now()
            ]);
        }
    }

    /**
     * Create message record with cost
     */
    public function createMessageRecord($channel, $phoneNumber, $message, $type = 'text', $status = 'pending')
    {
        $user = $channel->user;
        $costInEgp = $this->costPerMessage;

        // Check and reset WhatsApp balance if needed
        $this->checkAndResetWhatsAppBalance($user);

        return WhatsAppMessage::create([
            'channel_id' => $channel->id,
            'user_id' => $channel->user_id,
            'recipient_number' => $phoneNumber,
            'message_type' => $type,
            'message_content' => $message,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'cost_egp' => $costInEgp,
        ]);
    }

    /**
     * Deduct cost from WhatsApp balance for a sent message
     */
    public function deductCostForMessage(User $user, $costInEgp = null)
    {
        $costInEgp = $costInEgp ?? $this->costPerMessage;

        // Check if user has enough WhatsApp balance
        if (!$user->hasWhatsAppBalance($costInEgp)) {
            throw new \Exception('Insufficient WhatsApp balance. You need ' . number_format($costInEgp, 2) . ' EGP but have ' . number_format($user->whatsapp_balance_egp, 2) . ' EGP.');
        }

        // Deduct from WhatsApp balance
        $newBalance = $user->deductWhatsAppBalance($costInEgp);

        Log::info("WhatsApp balance deducted for message", [
            'user_id' => $user->id,
            'cost_egp' => $costInEgp,
            'new_balance' => $newBalance
        ]);

        return $newBalance;
    }

    /**
     * Process daily batch for a user
     */
    public function processDailyBatch(User $user, $date = null)
    {
        $date = $date ?? today();

        DB::beginTransaction();

        try {
            // Check and reset WhatsApp balance if needed
            $this->checkAndResetWhatsAppBalance($user);

            // Get unbilled messages for the user on the specified date
            $unbilledMessages = WhatsAppMessage::where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->whereNull('transaction_id')
                ->whereNull('daily_batch_id')
                ->get();

            if ($unbilledMessages->isEmpty()) {
                DB::rollBack();
                return null;
            }

            // Calculate total cost in EGP
            $totalMessages = $unbilledMessages->count();
            $totalCostEgp = $unbilledMessages->sum('cost_egp');

            // Check if user has enough WhatsApp balance
            if (!$user->hasWhatsAppBalance($totalCostEgp)) {
                DB::rollBack();
                throw new \Exception('Insufficient WhatsApp balance. You need ' . number_format($totalCostEgp, 2) . ' EGP but have ' . number_format($user->whatsapp_balance_egp, 2) . ' EGP.');
            }

            // Create daily batch record
            $dailyBatch = WhatsAppDailyBatch::create([
                'user_id' => $user->id,
                'date' => $date,
                'total_messages' => $totalMessages,
                'total_cost_egp' => $totalCostEgp,
                'status' => 'pending'
            ]);

            // Create transaction record (for tracking purposes)
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => 0, // No charge to main balance
                'type' => 'whatsapp_messages',
                'status' => 'completed',
                'description' => "WhatsApp Messages - {$date->format('Y-m-d')} ({$totalMessages} messages) - Deducted from WhatsApp balance",
                'reference' => "WHATSAPP_BATCH_{$dailyBatch->id}",
                'currency' => 'EGP',
                'exchange_rate' => 1.0,
            ]);

            // Update daily batch with transaction
            $dailyBatch->update([
                'transaction_id' => $transaction->id,
                'status' => 'processed',
                'processed_at' => now()
            ]);

            // Update messages with batch ID
            $unbilledMessages->each(function ($message) use ($dailyBatch) {
                $message->update(['daily_batch_id' => $dailyBatch->id]);
            });

            // Deduct from WhatsApp balance
            $newBalance = $user->deductWhatsAppBalance($totalCostEgp);

            DB::commit();

            Log::info("WhatsApp daily batch processed", [
                'user_id' => $user->id,
                'batch_id' => $dailyBatch->id,
                'transaction_id' => $transaction->id,
                'total_messages' => $totalMessages,
                'total_cost_egp' => $totalCostEgp,
                'new_whatsapp_balance' => $newBalance
            ]);

            return $dailyBatch;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("WhatsApp daily batch processing failed", [
                'user_id' => $user->id,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process all pending daily batches
     */
    public function processAllPendingBatches()
    {
        $pendingBatches = WhatsAppDailyBatch::pending()
            ->with('user')
            ->get();

        $processedCount = 0;
        $failedCount = 0;

        foreach ($pendingBatches as $batch) {
            try {
                $this->processDailyBatch($batch->user, $batch->date);
                $processedCount++;
            } catch (\Exception $e) {
                $batch->update([
                    'status' => 'failed'
                ]);
                $failedCount++;
                Log::error("Failed to process WhatsApp batch", [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'processed' => $processedCount,
            'failed' => $failedCount
        ];
    }

    /**
     * Get user's WhatsApp message statistics
     */
    public function getUserStats(User $user, $startDate = null, $endDate = null)
    {
        $query = WhatsAppMessage::where('user_id', $user->id);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $messages = $query->get();

        return [
            'total_messages' => $messages->count(),
            'sent_messages' => $messages->where('status', 'sent')->count(),
            'failed_messages' => $messages->where('status', 'failed')->count(),
            'total_cost_egp' => $messages->sum('cost_egp'),
            'total_cost_user_currency' => $this->getCostInUserCurrency($user, $messages->sum('cost_egp')),
            'by_type' => $messages->groupBy('message_type')->map->count(),
            'by_status' => $messages->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get daily batch statistics
     */
    public function getDailyBatchStats(User $user, $startDate = null, $endDate = null)
    {
        $query = WhatsAppDailyBatch::where('user_id', $user->id);

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $batches = $query->get();

        return [
            'total_batches' => $batches->count(),
            'processed_batches' => $batches->where('status', 'processed')->count(),
            'pending_batches' => $batches->where('status', 'pending')->count(),
            'failed_batches' => $batches->where('status', 'failed')->count(),
            'total_messages' => $batches->sum('total_messages'),
            'total_cost_egp' => $batches->sum('total_cost_egp'),
            'total_cost_user_currency' => $this->getCostInUserCurrency($user, $batches->sum('total_cost_egp')),
        ];
    }

    /**
     * Get cost per message for display
     */
    public function getDisplayCost(User $user)
    {
        $costInEgp = $this->costPerMessage;
        $costInUserCurrency = $this->getCostInUserCurrency($user, $costInEgp);
        $userCurrency = $user->currency ?? 'EGP';

        return [
            'egp' => $costInEgp,
            'user_currency' => $costInUserCurrency,
            'currency_code' => $userCurrency,
            'display' => \App\Helper\FinanceHelper::instance()->format_money($costInUserCurrency, $userCurrency)
        ];
    }

    /**
     * Get WhatsApp balance information for user
     */
    public function getWhatsAppBalanceInfo(User $user)
    {
        // Check and reset if needed
        $this->checkAndResetWhatsAppBalance($user);

        return [
            'current_balance_egp' => $user->whatsapp_balance_egp,
            'next_reset_date' => $user->getNextWhatsAppBalanceResetDate(),
            'days_until_reset' => $user->getDaysUntilWhatsAppBalanceReset(),
            'should_reset' => $user->shouldResetWhatsAppBalance(),
            'messages_remaining' => floor($user->whatsapp_balance_egp / $this->costPerMessage),
        ];
    }

    /**
     * Add WhatsApp balance to user
     */
    public function addWhatsAppBalance(User $user, $amount)
    {
        $newBalance = $user->addWhatsAppBalance($amount);

        Log::info("WhatsApp balance added", [
            'user_id' => $user->id,
            'amount_added' => $amount,
            'new_balance' => $newBalance
        ]);

        return $newBalance;
    }
}
