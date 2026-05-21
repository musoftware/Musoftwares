<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserMoneyTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'amount',
        'currency',
        'reason',
        'status',
        'fee_amount',
        'fee_currency',
        'exchange_rate',
        'converted_amount',
        'converted_currency',
        'sender_main_transaction_id',
        'sender_fee_transaction_id',
        'receiver_main_transaction_id',
        'receiver_fee_transaction_id',
        'admin_notes',
        'processed_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:10',
        'fee_amount' => 'decimal:10',
        'converted_amount' => 'decimal:10',
        'exchange_rate' => 'decimal:10',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function currencyModel()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function feeCurrencyModel()
    {
        return $this->belongsTo(Currency::class, 'fee_currency');
    }

    public function convertedCurrencyModel()
    {
        return $this->belongsTo(Currency::class, 'converted_currency');
    }

    // Transaction relationships
    public function senderMainTransaction()
    {
        return $this->belongsTo(Transaction::class, 'sender_main_transaction_id');
    }

    public function senderFeeTransaction()
    {
        return $this->belongsTo(Transaction::class, 'sender_fee_transaction_id');
    }

    public function receiverMainTransaction()
    {
        return $this->belongsTo(Transaction::class, 'receiver_main_transaction_id');
    }

    public function receiverFeeTransaction()
    {
        return $this->belongsTo(Transaction::class, 'receiver_fee_transaction_id');
    }

    // Get all related transactions
    public function getAllTransactions()
    {
        $transactions = collect();
        
        if ($this->sender_main_transaction_id) {
            $transactions->push($this->senderMainTransaction);
        }
        
        if ($this->sender_fee_transaction_id) {
            $transactions->push($this->senderFeeTransaction);
        }
        
        if ($this->receiver_main_transaction_id) {
            $transactions->push($this->receiverMainTransaction);
        }
        
        return $transactions->filter();
    }

    // Check if all transactions are properly linked
    public function hasAllTransactions()
    {
        return $this->sender_main_transaction_id && 
               $this->sender_fee_transaction_id && 
               $this->receiver_main_transaction_id;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function canBeProcessed()
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Get formatted amount with currency
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . ($this->currencyModel->currency ?? '');
    }

    public function getFormattedFeeAmountAttribute()
    {
        if (!$this->fee_amount) return '0.00';
        return number_format($this->fee_amount, 2) . ' ' . ($this->feeCurrencyModel->currency ?? '');
    }

    public function getFormattedConvertedAmountAttribute()
    {
        if (!$this->converted_amount) return $this->getFormattedAmountAttribute();
        return number_format($this->converted_amount, 2) . ' ' . ($this->convertedCurrencyModel->currency ?? '');
    }

    // Calculate total amount including fees
    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->fee_amount;
    }

    // Get status badge class
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'badge bg-warning';
            case self::STATUS_PROCESSING:
                return 'badge bg-info';
            case self::STATUS_COMPLETED:
                return 'badge bg-success';
            case self::STATUS_FAILED:
                return 'badge bg-danger';
            case self::STATUS_CANCELLED:
                return 'badge bg-secondary';
            case self::STATUS_REJECTED:
                return 'badge bg-danger';
            default:
                return 'badge bg-secondary';
        }
    }

    // Get status text
    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    // Check if transfer requires currency conversion
    public function requiresConversion()
    {
        return $this->sender->currency !== $this->receiver->currency;
    }

    // Get exchange rate for conversion
    public function getExchangeRate()
    {
        if (!$this->requiresConversion()) {
            return 1.0;
        }

        if ($this->exchange_rate) {
            return $this->exchange_rate;
        }

        // Get current exchange rate
        $rate = \App\Models\CurrenciesExchange::RateToday(
            1, 
            $this->sender->currency, 
            $this->receiver->currency
        );

        return $rate;
    }

    // Calculate converted amount
    public function calculateConvertedAmount()
    {
        if (!$this->requiresConversion()) {
            return $this->amount;
        }

        $exchangeRate = $this->getExchangeRate();
        return $this->amount * $exchangeRate;
    }

    // Calculate transfer fee
    public function calculateFee()
    {
        // Default fee: 1% of transfer amount
        $feePercentage = config('money_transfer.fee_percentage', 0.01);
        $feeAmount = $this->amount * $feePercentage;
        
        // Minimum fee
        $minFee = config('money_transfer.min_fee', 0.50);
        $feeAmount = max($feeAmount, $minFee);
        
        // Maximum fee
        $maxFee = config('money_transfer.max_fee', 10.00);
        $feeAmount = min($feeAmount, $maxFee);
        
        return round($feeAmount, 2);
    }

    // Check if sender has sufficient balance
    public function senderHasSufficientBalance()
    {
        $totalRequired = $this->amount + $this->fee_amount;
        return $this->sender->user_balance >= $totalRequired;
    }

    // Process the transfer
    public function process()
    {
        if (!$this->canBeProcessed()) {
            throw new \Exception('Transfer cannot be processed in current status');
        }

        if (!$this->senderHasSufficientBalance()) {
            throw new \Exception('Insufficient balance for transfer');
        }

        DB::transaction(function () {
            // Update status
            $this->status = self::STATUS_PROCESSING;
            $this->save();

            // Deduct main amount from sender
            $senderMainTransactionId = $this->sender->add_balance(
                -$this->amount,
                "Money transfer to " . $this->receiver->name,
                'sent',
                $this->currency
            );

            // Deduct fee from sender
            $senderFeeTransactionId = $this->sender->add_balance(
                -$this->fee_amount,
                "Transfer fee for money transfer to " . $this->receiver->name,
                'sent',
                $this->currency
            );

            // Add to receiver (with conversion if needed)
            $receiverAmount = $this->requiresConversion() ? 
                $this->calculateConvertedAmount() : $this->amount;

            $receiverMainTransactionId = $this->receiver->add_balance(
                $receiverAmount,
                "Money transfer from " . $this->sender->name,
                'received',
                $this->receiver->currency
            );

            // Update transfer record with transaction IDs
            $this->status = self::STATUS_COMPLETED;
            $this->processed_at = now();
            $this->converted_amount = $receiverAmount;
            $this->converted_currency = $this->receiver->currency;
            $this->exchange_rate = $this->getExchangeRate();
            $this->sender_main_transaction_id = $senderMainTransactionId;
            $this->sender_fee_transaction_id = $senderFeeTransactionId;
            $this->receiver_main_transaction_id = $receiverMainTransactionId;
            $this->save();

            // Create transaction records for both users
            $this->createTransferTransactions();
        });

        return true;
    }

    // Cancel the transfer
    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Transfer cannot be cancelled in current status');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        return true;
    }

    // Create transaction records for the transfer
    private function createTransferTransactions()
    {
        // Log the transfer with transaction IDs
        \Log::info('User money transfer completed', [
            'transfer_id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'fee_amount' => $this->fee_amount,
            'converted_amount' => $this->converted_amount,
            'converted_currency' => $this->converted_currency,
            'exchange_rate' => $this->exchange_rate,
            'sender_main_transaction_id' => $this->sender_main_transaction_id,
            'sender_fee_transaction_id' => $this->sender_fee_transaction_id,
            'receiver_main_transaction_id' => $this->receiver_main_transaction_id
        ]);
    }

    // Static method to create a new transfer
    public static function createTransfer($senderId, $receiverId, $amount, $currency, $reason = null)
    {
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        // Validate transfer
        if ($sender->id === $receiver->id) {
            throw new \Exception('Cannot transfer money to yourself');
        }

        if ($amount <= 0) {
            throw new \Exception('Transfer amount must be greater than zero');
        }

        // Check if feature is enabled
        if (!config('money_transfer.enabled', true)) {
            throw new \Exception(config('money_transfer.disabled_message', 'Money transfer is disabled'));
        }

        // Calculate fee
        $transfer = new self();
        $transfer->sender_id = $senderId;
        $transfer->receiver_id = $receiverId;
        $transfer->amount = $amount;
        $transfer->currency = $currency;
        $transfer->reason = $reason;
        $transfer->status = self::STATUS_PENDING;
        $transfer->fee_amount = $transfer->calculateFee();
        $transfer->fee_currency = $currency;

        // Check if sender has sufficient balance
        if (!$transfer->senderHasSufficientBalance()) {
            throw new \Exception('Insufficient balance for transfer');
        }

        $transfer->save();

        return $transfer;
    }
}
