<?php

namespace App\Models;

use App\Events\InvoiceCreated;
use App\Helpers\FinanceHelper;
use App\Traits\HasRecurringSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecurringInvoice extends Model
{
    use HasFactory, SoftDeletes, HasRecurringSchedule;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function current_amount()
    {
        return $this->amount;
    }

    public function current_amount_str()
    {
        return FinanceHelper::instance()->format_money($this->current_amount(), $this->currency_id);
    }

    public function records(): HasMany
    {
        return $this->hasMany(RecurringInvoiceRecord::class);
    }

    public function details()
    {
        if ($this->recurring == 'day') {
            return '';
        }
        if ($this->recurring == 'month') {
            return $this->recurring_times_month;
        }
        if ($this->recurring == 'week') {
            return $this->recurring_times_week;
        }
        if ($this->recurring == 'year') {
            return $this->recurring_times_year;
        }
    }

    public function apply()
    {
        $now = Carbon::now();
        if ($this->isToday($now)) {
            if (! $this->createdBefore($now)) {
                $user = $this->user;
                if (! $user) {
                    return;
                }

                $userCurrencyId = $user->currency_id ?? $user->currency;
                if (! $userCurrencyId) {
                    return;
                }

                // Convert amount to user's currency
                $convertedAmount = CurrenciesExchange::RateToday($this->amount, $this->currency_id, $userCurrencyId);

                DB::transaction(function () use ($user, $userCurrencyId, $convertedAmount, $now) {
                    $invoice = new Invoice;
                    $invoice->uuid = (string) Str::uuid();
                    $invoice->user_id = $user->id;
                    $invoice->currency_id = $userCurrencyId;
                    $invoice->status = 'unpaid';
                    $invoice->job_status = 'pending';
                    $invoice->save();

                    $item = new InvoiceItem;
                    $item->invoice_id = $invoice->id;
                    $item->item_title = $this->title;
                    $item->item_type = 'simple';
                    $item->qty = 1;
                    $item->amount = $convertedAmount;
                    $item->save();

                    $invoice->unpaid = $invoice->total();
                    $invoice->save();

                    DB::table('recurring_invoice_records')->insert([
                        'recurring_invoice_id' => $this->id,
                        'invoice_id' => $invoice->id,
                        'unique_id' => $this->unique_id($now),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Fire after the transaction commits so any notification
                    // listener that reads the freshly-persisted invoice sees
                    // it (avoids race conditions in ShouldQueue handlers).
                    DB::afterCommit(function () use ($invoice, $user) {
                        $autoPaid = false;
                        try {
                            $user->try_pay_unpaid_invoices();
                            $freshInvoice = $invoice->fresh();
                            if ($freshInvoice && $freshInvoice->status === 'paid') {
                                $autoPaid = true;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('RecurringInvoice: failed during try_pay_unpaid_invoices', [
                                'recurring_invoice_id' => $this->id,
                                'user_id' => $user->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        try {
                            event(new InvoiceCreated($invoice->fresh()));
                        } catch (\Throwable $e) {
                            Log::warning('RecurringInvoice: failed to dispatch InvoiceCreated event', [
                                'recurring_invoice_id' => $this->id,
                                'invoice_id' => $invoice->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        if (! $autoPaid) {
                            try {
                                $user->notify(new \App\Notifications\RecurringInvoiceInsufficientBalanceNotification($invoice->fresh()));
                            } catch (\Throwable $e) {
                                Log::warning('RecurringInvoice: failed to dispatch insufficient balance notification', [
                                    'recurring_invoice_id' => $this->id,
                                    'invoice_id' => $invoice->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    });
                });
            }
        }
    }

    public function generateMissingRuns(?Carbon $until = null): int
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $until = $until ? $until->copy()->setTimezone($timezone)->endOfDay() : Carbon::today($timezone)->endOfDay();
        $startDate = Carbon::parse($this->start_date)->setTimezone($timezone)->startOfDay();

        if ($startDate->gt($until)) {
            return 0;
        }

        $user = $this->user;
        if (! $user) {
            return 0;
        }

        $userCurrencyId = $user->currency_id ?? $user->currency;
        if (! $userCurrencyId) {
            return 0;
        }

        $convertedAmount = CurrenciesExchange::RateToday($this->amount, $this->currency_id, $userCurrencyId);

        $generatedCount = 0;
        $diffDays = $startDate->diffInDays($until);

        for ($i = 0; $i <= $diffDays; $i++) {
            $checkDate = $startDate->copy()->addDays($i);
            if ($this->isToday($checkDate) && ! $this->createdBefore($checkDate)) {
                $targetTime = $checkDate->copy()->setTime(3, 0, 0);

                DB::transaction(function () use ($user, $userCurrencyId, $convertedAmount, $checkDate, $targetTime) {
                    $invoice = new Invoice;
                    $invoice->uuid = (string) Str::uuid();
                    $invoice->user_id = $user->id;
                    $invoice->currency_id = $userCurrencyId;
                    $invoice->status = 'unpaid';
                    $invoice->job_status = 'pending';
                    $invoice->created_at = $targetTime;
                    $invoice->updated_at = $targetTime;
                    $invoice->save();

                    $item = new InvoiceItem;
                    $item->invoice_id = $invoice->id;
                    $item->item_title = $this->title;
                    $item->item_type = 'simple';
                    $item->qty = 1;
                    $item->amount = $convertedAmount;
                    $item->created_at = $targetTime;
                    $item->updated_at = $targetTime;
                    $item->save();

                    $invoice->unpaid = $invoice->total();
                    $invoice->save();

                    DB::table('recurring_invoice_records')->insert([
                        'recurring_invoice_id' => $this->id,
                        'invoice_id' => $invoice->id,
                        'unique_id' => $this->unique_id($checkDate),
                        'created_at' => $targetTime,
                        'updated_at' => $targetTime,
                    ]);
                });

                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    private function unique_id($date)
    {
        if ($date instanceof Carbon) {
            $d = $date->format('Y-m-d');
        } else {
            $d = date('Y-m-d', strtotime($date));
        }

        return $this->id.'-'.$d;
    }

    public function createdBefore($date)
    {
        $is_exist = DB::selectOne('select count(id) as is_exist_count from recurring_invoice_records where unique_id=?', [$this->unique_id($date)]);

        return $is_exist->is_exist_count == 1;
    }

    public function isToday($date)
    {
        if ($this->recurring == 'day') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInDays($t);
            if ($diff == 0) {
                return true;
            }

            return $diff % max(1, $this->recurring_times) == 0;
        }

        if ($this->recurring == 'week') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInWeeks($t);
            $days = explode(',', $this->recurring_times_week);

            if ($diff % max(1, $this->recurring_times) == 0) {
                return in_array(date('l', strtotime($date)), $days);
            }
        }

        if ($this->recurring == 'month') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInMonths($t);
            $days = explode(',', $this->recurring_times_month);

            if ($diff % max(1, $this->recurring_times) == 0) {
                if (date('n', strtotime($date)) == '2') {
                    foreach ($days as $day) {
                        if ($day > date('t', strtotime($date))) {
                            return date('t', strtotime($date)) == date('d', strtotime($date));
                        }
                    }
                }

                return in_array(date('d', strtotime($date)), $days);
            }
        }

        if ($this->recurring == 'year') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInYears($t);
            $day_months = explode(',', $this->recurring_times_year);
            if ($diff % max(1, $this->recurring_times) == 0) {
                return in_array(date('j-n', strtotime($date)), $day_months);
            }
        }

        return false;
    }

    public function delete_with_records()
    {
        $this->records()->delete();
        $this->delete();
    }
}
