<?php

namespace App\Models;

use App\Helpers\BalancesHelper;
use App\Helpers\FinanceHelper;
use App\Helpers\TextHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'type',
        'project_id',
        'currency_id',
        'reverse_transaction_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::observe(\App\Observers\TransactionObserver::class);

        static::saving(function ($transaction) {
            // Force user currency conversion if different
            if ($transaction->user_id && $transaction->user) {
                if (!$transaction->user->currency_id) {
                    throw new \Exception("User {$transaction->user_id} is missing a currency_id. Global fallback is prohibited.");
                }
                
                $userCurrencyId = $transaction->user->currency_id;
                $currentCurrencyId = $transaction->currency_id ?? $transaction->currency;
                
                if (!$currentCurrencyId) {
                    // If no currency was explicitly passed for the transaction, assume it is in the user's native currency.
                    $currentCurrencyId = $userCurrencyId;
                    $transaction->currency_id = $userCurrencyId;
                }
                
                if ($currentCurrencyId != $userCurrencyId) {
                    $date = $transaction->created_at ?? now();
                    $transaction->amount = \App\Models\CurrenciesExchange::RateByDate(
                        $date,
                        $transaction->amount,
                        $currentCurrencyId,
                        $userCurrencyId
                    );
                    $transaction->currency_id = $userCurrencyId;
                }
            }

            $currency = $transaction->currency_id ?? $transaction->currency;
            if (!$currency) {
                throw new \Exception("Transaction is missing an associated currency relation and no user fallback is available.");
            }

            $businessCurrencyId = \App\Models\AdminSettings::business_currency();
            if (is_object($businessCurrencyId)) {
                $businessCurrencyId = $businessCurrencyId->id;
            }
            
            $date = $transaction->created_at ?? now();
            $transaction->business_amount = \App\Models\CurrenciesExchange::RateByDate(
                $date,
                $transaction->amount,
                $currency,
                $businessCurrencyId
            );
            $transaction->business_calculated = true;
        });
    }

    public static function get_sum_balance($date)
    {
        $sum = Transaction::query()->where('created_at', '<=', Carbon::parse($date));
        return $sum->sum('business_amount');
    }


    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class);
    }

    public function enc_id()
    {
        return TextHelper::instance()->crockford_encode((string) $this->id);
    }

    public function color()
    {
        if ($this->type == 'received') {
            return 'badge bg-primary';
        }
        if ($this->type == 'refunded') {
            return 'badge bg-danger';
        }
        if ($this->type == 'sent') {
            return 'badge bg-success';
        }
        if ($this->type == 'earned') {
            return 'badge bg-warning';
        }
        if ($this->type == 'used') {
            return 'badge bg-success';
        }
    }

    public function delete_with_balance()
    {
        $client = $this->user()->first();
        $project = $this->project()->first();

        DB::transaction(function () use ($client, $project) {
            $this->delete();

            BalancesHelper::UpdateBalance($client, $project);

            //            $client->change_balance($this->currency);
            //            if (isset($project)) {
            //                $project->change_balance($this->currency);
            //            }
        });
    }

    public function client_view_type()
    {
        if ($this->type == 'received')
            return 'Deposit';
        if ($this->type == 'used')
            return 'Paid';
        if ($this->type == 'sent')
            return 'Received';
        if ($this->type == 'earn')
            return 'Earned';
        return $this->type;
    }

    public function amount_str()
    {
        return FinanceHelper::instance()->format_money($this->amount, $this->currency_id);
    }

    public function balance()
    {
        return Transaction::query()->where('user_id', $this->user_id)
            ->where('id', '<=', $this->id)->sum('amount');
        //        return FinanceHelper::instance()->format_money($this->amount, $this->currency);
    }

    public function balance_str()
    {
        return FinanceHelper::instance()->format_money($this->balance(), $this->currency_id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the original transaction that this transaction reverses
     */
    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reverse_transaction_id');
    }

    /**
     * Get the reverse transaction for this transaction
     */
    public function reverseTransaction()
    {
        return $this->hasOne(Transaction::class, 'reverse_transaction_id');
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
        // Only "sent" transactions can be reversed
        if ($this->type !== 'sent') {
            throw new \Exception('Only "sent" transactions can be reversed. This transaction type is: ' . $this->type);
        }

        // Check if already reversed
        if ($this->isReversed()) {
            throw new \Exception('Transaction has already been reversed');
        }

        $user = $this->user;
        if (!$user) {
            throw new \Exception('Transaction has no associated user');
        }

        // Create reverse transaction with opposite amount
        $reverseAmount = -1 * $this->amount;
        $reverseReason = $reason ?? ('Reverse transaction #' . $this->id . ($this->reason ? ' - ' . $this->reason : ''));

        // Determine reverse type based on original type
        $reverseType = 'earned'; // For "sent" transactions, reverse is "earned"

        $reverseTransaction = new Transaction();
        $reverseTransaction->user_id = $this->user_id;
        $reverseTransaction->amount = $reverseAmount;
        $reverseTransaction->reason = $reverseReason;
        $reverseTransaction->type = $reverseType;
        $reverseTransaction->project_id = $this->project_id;
        $reverseTransaction->currency_id = $this->currency_id;
        $reverseTransaction->reverse_transaction_id = $this->id;

        DB::transaction(function () use ($reverseTransaction, $user) {
            $reverseTransaction->save();

            // Update balances using the User model's add_balance method logic
            $project = $reverseTransaction->project;

            $user->increment('user_balance', $reverseTransaction->amount);
            if ($project) {
                $project->increment('project_balance', $reverseTransaction->amount);
            }

            if (in_array($reverseTransaction->type, ['received', 'sent', 'refunded'])) {
                $user->increment('total_paid', $reverseTransaction->amount);
                if ($project) {
                    $project->increment('total_paid', $reverseTransaction->amount);
                }
            }

            // Recalculate balances to ensure consistency
            BalancesHelper::UpdateBalance($user, $project);
        });

        return $reverseTransaction;
    }

    public static function add_income_balance($amount, $reason, $currency = null)
    {
        if ($amount == 0)
            return null;
        $user_id = null;
        $c = new Transaction();
        $c->user_id = $user_id;

        $c->type = 'received';
        $c->amount = $amount;
        $c->reason = $reason;
        $c->currency_id = $currency;

        DB::transaction(function () use ($c) {
            $c->save();
        });
        return $c->id;
    }
}
