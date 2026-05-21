<?php

namespace Modules\Core\Models;

use App\Helpers\BalancesHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class InvoiceCostAccrual extends Model
{
    protected $guarded = [];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function withdrawRequest(): BelongsTo
    {
        return $this->belongsTo(UserReferralRequestWithdraw::class, 'user_referral_request_withdraw_id');
    }

    /**
     * Sum of pending accruals for user, converted to user's currency.
     */
    public static function pendingTotalInUserCurrency(User $user): float
    {
        $total = 0.0;
        $rows = static::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get(['amount', 'currency']);

        foreach ($rows as $row) {
            $total += CurrenciesExchange::RateToday((float) $row->amount, (int) $row->currency, (int) $user->currency);
        }

        return round($total, 3);
    }

    /**
     * Raw transaction sum for user (same basis as BalancesHelper::CalcBalance).
     */
    public static function transactionBalanceInUserCurrency(User $user): float
    {
        $balance = $user->transactions()
            ->groupBy('currency')
            ->selectRaw('sum(amount) as total_amount, currency')
            ->get();

        $total = 0.0;
        foreach ($balance as $item) {
            $total += CurrenciesExchange::RateToday((float) $item->total_amount, (int) $item->currency, (int) $user->currency);
        }

        return round($total, 3);
    }

    /**
     * Credit pending accruals FIFO until at least $needInUserCurrency is covered (in user currency).
     * Used when a withdraw is approved to top up balance before `sent`.
     */
    public static function creditPendingFifo(User $user, float $needInUserCurrency, UserReferralRequestWithdraw $withdraw): float
    {
        if ($needInUserCurrency <= 0) {
            return 0.0;
        }

        $credited = 0.0;

        DB::transaction(function () use ($user, $needInUserCurrency, $withdraw, &$credited) {
            $accruals = static::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($accruals as $accrual) {
                if ($credited >= $needInUserCurrency - 0.0001) {
                    break;
                }

                $sliceInUser = CurrenciesExchange::RateToday((float) $accrual->amount, (int) $accrual->currency, (int) $user->currency);
                $remaining = $needInUserCurrency - $credited;
                if ($sliceInUser <= $remaining + 0.0001) {
                    $tid = $user->add_balance(
                        (float) $accrual->amount,
                        'Invoice #' . $accrual->invoice_id . ' cost',
                        'earned',
                        (int) $accrual->currency
                    );
                    $accrual->update([
                        'status' => 'settled',
                        'transaction_id' => $tid,
                        'user_referral_request_withdraw_id' => $withdraw->id,
                    ]);
                    $credited += $sliceInUser;
                } else {
                    // Split: partial accrual consumption (invoice currency)
                    $ratio = $remaining / max($sliceInUser, 0.00001);
                    $partialAmount = round((float) $accrual->amount * $ratio, 3);
                    if ($partialAmount <= 0) {
                        break;
                    }
                    $tid = $user->add_balance(
                        $partialAmount,
                        'Invoice #' . $accrual->invoice_id . ' cost (partial)',
                        'earned',
                        (int) $accrual->currency
                    );
                    $rest = round((float) $accrual->amount - $partialAmount, 3);
                    $accrual->update([
                        'amount' => $partialAmount,
                        'status' => 'settled',
                        'transaction_id' => $tid,
                        'user_referral_request_withdraw_id' => $withdraw->id,
                    ]);
                    static::query()->create([
                        'invoice_id' => $accrual->invoice_id,
                        'user_id' => $accrual->user_id,
                        'amount' => $rest,
                        'currency' => $accrual->currency,
                        'status' => 'pending',
                    ]);
                    $credited += $remaining;
                    break;
                }
            }
        });

        BalancesHelper::UpdateBalance($user);

        return $credited;
    }
}
