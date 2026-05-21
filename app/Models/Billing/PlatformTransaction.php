<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PlatformTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'amount',
        'business_amount',
        'currency',
        'reason',
        'type',
        'reverse_transaction_id',
    ];

    /**
     * The related user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The related invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(PlatformInvoice::class, 'invoice_id');
    }

    /**
     * Get the original transaction that this transaction reverses
     */
    public function originalTransaction()
    {
        return $this->belongsTo(self::class, 'reverse_transaction_id');
    }

    /**
     * Get the reverse transaction for this transaction
     */
    public function reverseTransaction()
    {
        return $this->hasOne(self::class, 'reverse_transaction_id');
    }

    /**
     * Check if this transaction has been reversed
     */
    public function isReversed()
    {
        return $this->reverseTransaction()->exists();
    }

    /**
     * Check if this transaction is a reverse transaction
     */
    public function isReverseTransaction()
    {
        return $this->reverse_transaction_id !== null;
    }

    /**
     * Create a reverse transaction for this transaction
     * This creates a new transaction with opposite amount to zero out the balance
     */
    public function createReverse($reason = null)
    {
        if ($this->type !== 'sent' && $this->type !== 'received') {
            throw new \Exception('Only "sent" or "received" transactions can be reversed. This transaction type is: ' . $this->type);
        }

        if ($this->isReversed()) {
            throw new \Exception('Transaction has already been reversed');
        }

        $user = $this->user;
        if (!$user) {
            throw new \Exception('Transaction has no associated user');
        }

        $reverseAmount = -1 * $this->amount;
        $reverseReason = $reason ?? ('Reverse transaction #' . $this->id . ($this->reason ? ' - ' . $this->reason : ''));
        
        $reverseType = $this->type === 'sent' ? 'earned' : 'refunded';

        $reverseTransaction = new self();
        $reverseTransaction->user_id = $this->user_id;
        $reverseTransaction->invoice_id = $this->invoice_id;
        $reverseTransaction->amount = $reverseAmount;
        $reverseTransaction->business_amount = -1 * $this->business_amount;
        $reverseTransaction->reason = $reverseReason;
        $reverseTransaction->type = $reverseType;
        $reverseTransaction->currency = $this->currency;
        $reverseTransaction->reverse_transaction_id = $this->id;

        DB::transaction(function () use ($reverseTransaction, $user) {
            $reverseTransaction->save();

            $wallet = $user->getWallet();
            if ($reverseTransaction->type === 'earned') {
                $wallet->earned_balance += $reverseTransaction->amount; // usually amount is negative here so this decreases it
            } else {
                $wallet->balance += $reverseTransaction->amount;
            }
            $wallet->save();
        });

        return $reverseTransaction;
    }

    /**
     * Core method to add balance or deduct balance for a user.
     */
    public static function addBalance($userId, $amount, $reason, $type = 'received', $currency = 'USD', $invoiceId = null)
    {
        if ((float) $amount == 0) {
            return null;
        }

        $transaction = new self();
        $transaction->user_id = $userId;
        $transaction->invoice_id = $invoiceId;
        $transaction->amount = $amount;
        $transaction->reason = $reason;
        $transaction->type = $type;
        $transaction->currency = $currency;
        
        // TODO: Integrate actual exchange rate here for business_amount. Using amount as fallback for now.
        $transaction->business_amount = $amount;

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->save();
            
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $wallet = $user->getWallet();
                    if ($transaction->type === 'earned') {
                        $wallet->earned_balance += $transaction->amount;
                    } else {
                        $wallet->balance += $transaction->amount;
                    }
                    $wallet->save();
                }
            }

            return $transaction->id;
        });
    }
}
