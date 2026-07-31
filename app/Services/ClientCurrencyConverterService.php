<?php

namespace App\Services;

use App\Helpers\BalancesHelper;
use App\Models\CurrenciesExchange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Physically rewrites every stored monetary amount owned by a client from one
 * currency to another using the historical exchange rate at each record's
 * created_at, then recomputes the denormalized balance columns from source.
 *
 * IMPORTANT:
 * - All row updates are performed via raw DB::table()->update() so the
 *   Transaction / CostTransaction "saving" observers (which would otherwise
 *   re-convert the amount and recompute business_amount) are fully bypassed.
 * - Only rows still carrying the OLD currency are touched (double-conversion
 *   guard): once rewritten they carry the new currency and a later change will
 *   not touch them again.
 *
 * Known limitation: business_amount on transactions and cost_transactions is
 * intentionally NOT recalculated (it goes stale). Recompute those manually if
 * business-currency reports are required.
 */
class ClientCurrencyConverterService extends BaseService
{
    private const CHUNK_SIZE = 500;

    /**
     * Convert all monetary data for $user from $oldId currency to $newId currency.
     *
     * Precondition: $user->currency_id must already equal $newId (the caller is
     * expected to persist the new currency first, as the AdminUserService trigger
     * does after save). The balance recompute relies on $user->currency_id being
     * the new currency so the source rows (now rewritten to $newId) convert 1:1.
     *
     * @return array{counts: array<string,int>, fallback_warnings: int}
     */
    public function convert(User $user, int $oldId, int $newId): array
    {
        if ($oldId <= 0 || $oldId === $newId) {
            return ['counts' => [], 'fallback_warnings' => 0];
        }

        $result = $this->executeInTransaction(function () use ($user, $oldId, $newId) {
            $counts = [
                'transactions' => 0,
                'invoices' => 0,
                'invoice_items' => 0,
                'invoice_item_timers' => 0,
                'cost_transactions' => 0,
                'recurring_invoices' => 0,
                'user_referral_request_withdraws' => 0,
            ];
            $fallbackWarnings = 0;

            // The in-memory rate cache may hold pairs populated earlier in the
            // request lifecycle; start the conversion from a clean slate.
            CurrenciesExchange::flushCache();

            $convert = function (string $date, $amount) use ($oldId, $newId, &$fallbackWarnings): float {
                $original = (float) $amount;
                $converted = (float) CurrenciesExchange::RateByDate($date, $original, $oldId, $newId);
                // RateByDate silently falls back to 1.0 when no historical rate
                // is available for a date. Flag those rows so they are visible
                // in the audit log (they converted 1:1).
                if (abs($original) > 0.0000001 && abs($converted - $original) < 0.0000001) {
                    $fallbackWarnings++;
                }

                return $converted;
            };

            // 1. transactions
            DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('transactions')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                            'currency_id' => $newId,
                        ]);
                        $counts['transactions']++;
                    }
                });

            // 2. invoices — capture the converted invoice id set BEFORE flipping
            //    currency so items/timers can be scoped to exactly these invoices.
            $invoiceIds = DB::table('invoices')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->pluck('id')
                ->all();

            DB::table('invoices')
                ->whereIn('id', $invoiceIds)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('invoices')->where('id', $row->id)->update([
                            'paid' => $convert($row->created_at, $row->paid),
                            'unpaid' => $convert($row->created_at, $row->unpaid),
                            'discount' => $convert($row->created_at, $row->discount),
                            'second_discount' => $convert($row->created_at, $row->second_discount),
                            'tax_value' => $convert($row->created_at, $row->tax_value),
                            'cost' => $convert($row->created_at, $row->cost),
                            'currency_id' => $newId,
                        ]);
                        $counts['invoices']++;
                    }
                });

            // 3. invoice_items (no user_id/currency_id column — scoped by invoice set)
            DB::table('invoice_items')
                ->whereIn('invoice_id', $invoiceIds)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('invoice_items')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                        ]);
                        $counts['invoice_items']++;
                    }
                });

            // 4. invoice_item_timers
            DB::table('invoice_item_timers')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('invoice_item_timers')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                            'currency_id' => $newId,
                        ]);
                        $counts['invoice_item_timers']++;
                    }
                });

            // 5. cost_transactions
            DB::table('cost_transactions')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('cost_transactions')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                            'currency_id' => $newId,
                        ]);
                        $counts['cost_transactions']++;
                    }
                });

            // 6. recurring_invoices
            DB::table('recurring_invoices')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('recurring_invoices')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                            'currency_id' => $newId,
                        ]);
                        $counts['recurring_invoices']++;
                    }
                });

            // 7. user_referral_request_withdraws
            DB::table('user_referral_request_withdraws')
                ->where('user_id', $user->id)
                ->where('currency_id', $oldId)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use ($convert, $newId, &$counts) {
                    foreach ($rows as $row) {
                        DB::table('user_referral_request_withdraws')->where('id', $row->id)->update([
                            'amount' => $convert($row->created_at, $row->amount),
                            'currency_id' => $newId,
                        ]);
                        $counts['user_referral_request_withdraws']++;
                    }
                });

            // Recompute denormalized balances from source (all rows are now in
            // $newId, identical to the user's currency, so conversion is 1:1).
            $balances = BalancesHelper::instance();
            $balances->CalcBalance($user);
            $balances->CalcTotalSpend($user);
            $balances->CalcCostBalance($user);
            $balances->CalcWithdrawingCommission($user);
            $balances->CalcWithdrawnCommission($user);

            $user->projects()->orderBy('id')->chunk(self::CHUNK_SIZE, function ($projects) use ($balances, $user) {
                foreach ($projects as $project) {
                    $balances->CalcBalance($user, $project);
                    $balances->CalcTotalSpend($user, $project);
                }
            });

            if (!empty($invoiceIds)) {
                \App\Models\Invoice::whereIn('id', $invoiceIds)->chunk(self::CHUNK_SIZE, function ($invoices) {
                    foreach ($invoices as $invoice) {
                        $invoice->updateCachedTotal();
                    }
                });
            }

            return ['counts' => $counts, 'fallback_warnings' => $fallbackWarnings];
        }, 'Client currency conversion failed');

        Log::info('Client currency converted', [
            'user_id' => $user->id,
            'old_currency_id' => $oldId,
            'new_currency_id' => $newId,
            'counts' => $result['counts'],
            'fallback_warnings' => $result['fallback_warnings'],
            'completed_at' => now()->toDateTimeString(),
        ]);

        return $result;
    }
}
