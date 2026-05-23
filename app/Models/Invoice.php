<?php

namespace App\Models;

use App\Events\InvoicePaid;
use App\Helpers\ActionHelper;
use App\Helpers\BalancesHelper;
use App\Helpers\FinanceHelper;
use App\Helpers\TextHelper;
use App\Helpers\TimezoneHelper;
use App\Models\Operations\CharityCounter;
use App\Models\Transaction;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JamesMills\LaravelTimezone\Facades\Timezone;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'schedule' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });

        static::saving(function ($invoice) {
            unset($invoice->start_day);
        });
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class);
    }


    public function cost_transactions()
    {
        return $this->belongsToMany(CostTransaction::class);
    }

    protected function invoiceStatus(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ucfirst($this->status),
        );
    }

    protected function scheduledStartDate(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $schedule = $this->getSchedule();
                if ($schedule && isset($schedule['start_date'])) {
                    return Carbon::parse($schedule['start_date'])->format('Y-m-d');
                }
                return null;
            },
            set: fn ($value) => null,
        );
    }

    protected $cache_qty = null;

    public function contain_qty(): ?bool
    {
        if ($this->cache_qty === null) {
            $qty = 0;
            foreach ($this->items()->get() as $index => $item) {
                if ($index == 0) {
                    $qty = $item->qty;
                } else {
                    if ($qty != $item->qty) {
                        return $this->cache_qty = true;
                    }
                }
            }
            $this->cache_qty = false;
            return false;
        } else {
            return $this->cache_qty;
        }
    }

    public function date_str()
    {
        return TimezoneHelper::convertToLocal($this->created_at, 'D, j/m/Y');
    }

    public static function UnpaidInvoicesBusiness()
    {
        $invoices = static::query()->where(function ($query) {
            $query->where('status', 'unpaid');
            $query->orWhere('status', 'partially_paid');
        })->get();

        $unpaid = 0;

        foreach ($invoices as $invoice) {
            $unpaid += CurrenciesExchange::RateToday($invoice->unpaid, $invoice->currency, \App\Models\CurrenciesExchange::BusinessCurrency());
        }
        return $unpaid;
    }


    public static function UnpaidInvoices()
    {
        return static::query()->where(function ($query) {
            $query->where('status', 'unpaid');
            $query->orWhere('status', 'partially_paid');
        })->where('archive', '0');
    }

    public static function ArchiveInvoices()
    {
        return static::query()->where(function ($query) {
            $query->where('status', 'unpaid');
            $query->orWhere('status', 'partially_paid');
        })->where('archive', '1');
    }

    /**
     * Outstanding invoices for admin overdue alerts (matches unpaid listing semantics; excludes cancelled/paid/archived and zero-balance rows).
     */
    public function scopeOverdueForAdminDashboard($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'unpaid')
                ->orWhere('status', 'partially_paid');
        })
            ->where('archive', '0')
            ->where('unpaid', '>', 0)
            ->where('created_at', '<', now()->subDays(30));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function costPayableUser()
    {
        return $this->belongsTo(User::class, 'cost_payable_user_id');
    }

    public function costLines()
    {
        return $this->hasMany(InvoiceCostLine::class)->orderBy('sort_order');
    }

    /**
     * Total internal cost: sum of cost lines, or legacy single cost column.
     */
    public function totalInternalCost(): float
    {
        if ($this->relationLoaded('costLines')) {
            $sum = (float) $this->costLines->sum('amount');
        } else {
            $sum = (float) $this->costLines()->sum('amount');
        }
        if ($sum > 0 || ($this->relationLoaded('costLines') ? $this->costLines->isNotEmpty() : $this->costLines()->exists())) {
            return $sum;
        }

        return (float) $this->cost;
    }

    public function mark_as_paid()
    {

        $this->calculate_cost();

        if (!empty($this->project_id)) {
            $project = Project::find($this->project_id);
            $project->add_balance($this->total(), 'Invoice #' . $this->id, 'received', $this->id, $this->currency);
            $project->add_balance(-1 * $this->total(), 'Invoice #' . $this->id, 'used', $this->id, $this->currency);
        } else {
            $client = User::find($this->user_id);
            $client->add_balance($this->total(), 'Invoice #' . $this->id, 'received', $this->id, $this->currency);
            $client->add_balance(-1 * $this->total(), 'Invoice #' . $this->id, 'used', $this->id, $this->currency);
        }
        $this->user->calc_ref($this->total_min_cost(), $this->id, $this->currency);
        $this->paid = $this->total();
        $this->status = 'paid';
        $this->job_status = 'done';
        $this->clearSchedule();
        $this->save();

        $this->refresh();
        $this->calculate_cost();

        // إضافة جنيه واحد تلقائياً لعداد الخير عند دفع الفاتورة
        $this->addCharityAmount();

        // Fire InvoicePaid event for notifications
        event(new \App\Events\InvoicePaid($this->user, $this));
    }

    public static function createInvoice($client, $project, $request)
    {
        $invoice = new Invoice();
        $invoice->user_id = Auth::id();
        $invoice->currency = $client->currency;
        if ($project !== null) {
            $invoice->project_id = $project->id;
        }
        if ($request !== null) {
            $invoice->request_id = $request->id;
        }
        $client->invoices()->save($invoice);
        return $invoice;
    }

    public function cloneInvoice()
    {
        $new_inc = new Invoice();
        $new_inc->user_id = $this->user_id;
        $new_inc->project_id = $this->project_id;
        $new_inc->currency = $this->currency;
        return $new_inc;
    }


    public function transfer_to_project($project_id)
    {
        DB::transaction(function () use ($project_id) {

            if (empty($project_id) || !is_numeric($project_id)) {
                $this->project_id = null;
            } else {
                $this->project_id = intval($project_id);
            }
            $this->save();

            foreach ($this->items()->get() as $item) {
                foreach ($item->timers()->get() as $timer) {
                    $timer->project_id = $this->project_id;
                    $timer->save();
                }
            }

            foreach ($this->transactions()->get() as $t) {
                $t->project_id = $this->project_id;
                $t->save();
            }
        });
    }

    public function unpaid_str()
    {
        return FinanceHelper::instance()->format_money(round($this->unpaid_total(), 2), $this->currency);
    }

    public function tax()
    {
        if ($this->status == 'unpaid') {
            if ($this->user->invoice_taxable == '1') {
                $business_tax = AdminSettings::GetValue('business_tax', 22.5);
                $tax_calc = $business_tax * $this->sub_total() / 100;
                $this->tax_value = $tax_calc;
                $this->save();
                return $tax_calc;
            } else {
                $this->tax_value = 0;
                $this->save();
                return 0;
            }
        } else {
            return $this->tax_value;
        }
    }


    public function tax_str()
    {
        return FinanceHelper::instance()->format_money($this->tax(), $this->currency);
    }

    public function sub_total()
    {
        $total = 0;
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        foreach ($items as $item) {
            $total += $item->total();
        }

        return (float) $total;
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** Computed total (no DB column); avoids Eloquent treating total() as a relationship. */
    public function getTotalAttribute(): float
    {
        return round(($this->sub_total() + $this->tax()) - ($this->discount + $this->second_discount), 2);
    }

    public function total(): float
    {
        return $this->getTotalAttribute();
    }

    public function total_min_cost()
    {
        return $this->total() - $this->totalInternalCost();
    }

    /**
     * Get the total commission amount for this invoice (sum of all items' commissions)
     */
    public function commission_amount()
    {
        $totalCommission = 0;
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        foreach ($items as $item) {
            $totalCommission += $item->commission_amount();
        }
        return $totalCommission;
    }

    /**
     * Get the base total without commission (sum of all items' base totals)
     */
    public function base_total()
    {
        $totalBaseAmount = 0;
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        foreach ($items as $item) {
            $totalBaseAmount += $item->base_total();
        }
        return round($totalBaseAmount, 2);
    }

    /**
     * Get formatted commission amount string
     */
    public function commission_amount_str()
    {
        return FinanceHelper::instance()->format_money($this->commission_amount(), $this->currency);
    }

    /**
     * Get formatted base total string (without commission)
     */
    public function base_total_str()
    {
        return FinanceHelper::instance()->format_money($this->base_total(), $this->currency);
    }

    /**
     * Get the total including commission (base total + commission)
     */
    public function total_with_commission()
    {
        return $this->base_total() + $this->commission_amount();
    }

    /**
     * Get formatted total with commission string
     */
    public function total_with_commission_str()
    {
        return FinanceHelper::instance()->format_money($this->total_with_commission(), $this->currency);
    }

    public function unpaid_total()
    {
        return round($this->total() - $this->paid, 2);
    }

    public function unpaid_amount()
    {
        return $this->unpaid_total();
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function date()
    {
        return TimezoneHelper::convertToLocal($this->created_at, 'd/m/Y');
    }

    public function total_str()
    {
        return FinanceHelper::instance()->format_money($this->total(), $this->currency);
    }

    public function total_timer()
    {
        $total_timer = 0.0;
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        foreach ($items as $item) {
            $timers = $item->relationLoaded('timers') ? $item->timers : $item->timers()->get();
            foreach ($timers as $timer) {
                $total_timer += (float) $timer->diff();
            }
        }
        return $total_timer;
    }

    public function total_timer_str()
    {
        return TextHelper::secondsToTime($this->total_timer());
    }

    public function status_str()
    {
        return ucfirst($this->status);
    }

    public function status_icon()
    {
        switch ($this->status) {
            case 'unpaid':
                return 'processing';
            case 'paid':
                return 'completed';
        }
    }

    public function color()
    {
        switch ($this->status) {
            case 'unpaid':
                return '#c6e1c6';
            case 'paid':
                return '#5fbe5f';
            case 'partially_paid':
                return '#99ff99';
            case 'cancelled':
                return '#CCC';
        }
    }

    public function job_status_str()
    {
        return __($this->job_status ? ucfirst($this->job_status) : 'Pending');
    }

    public function job_status_icon()
    {
        switch ($this->job_status) {
            case 'done':
                return 'completed';
            case 'processing':
                return 'processing';
            case 'pending':
            default:
                return 'pending';
        }
    }

    public function color_text()
    {
        switch ($this->status) {
            case 'unpaid':
                return '#000';
            case 'partially_paid':
            case 'cancelled':
            case 'paid':
                return '#fff';
        }
    }

    public function status_color()
    {
        switch ($this->status) {
            case 'unpaid':
                return 'bg-danger';
            case 'partially_paid':
                return 'bg-warning';
            case 'cancelled':
                return 'bg-gray';
            case 'paid':
                return 'bg-success';
        }
    }


    public function cancel_invoice()
    {
        DB::transaction(function () {
            if ($this->status == 'paid') {
                $this->client->calc_ref(-1 * $this->total(), $this->id, $this->currency);
            }
            if ($this->status == 'partially_paid') {
                $this->client->calc_ref(-1 * round($this->paid, 2), $this->id, $this->currency);
            }

            $this->delete_transactions();

            $this->status = 'cancelled';
            $this->save();

            if (!empty($this->project_id)) {
                $project = \App\Models\Project::find($this->project_id);
                $client = User::find($this->user_id);
                BalancesHelper::UpdateBalance($client, $project);
            } else {
                $client = User::find($this->user_id);
                BalancesHelper::UpdateBalance($client, null);
            }
        });
    }

    public function project_name()
    {
        return optional($this->project)->project_name;
    }

    public function business_total_str()
    {
        return FinanceHelper::instance()->format_money($this->business_total(), AdminSettings::GetValue('business_currency', 2));
    }

    public function enc_id()
    {
        return TextHelper::instance()->generateInvoiceId($this->created_at, (string) $this->id);
    }

    public function client_name_short()
    {
        return Str::words($this->client_name(), 2, '');
    }

    public function client_name()
    {
        return optional($this->user)->name;
    }

    public function business_total()
    {
        return CurrenciesExchange::RateToday($this->total(), $this->currency, AdminSettings::GetValue('business_currency', 2));
    }

    public function discount_str()
    {
        return FinanceHelper::instance()->format_money(($this->discount + $this->second_discount), $this->currency);
    }


    public function sub_total_str()
    {
        return FinanceHelper::instance()->format_money($this->sub_total(), $this->currency);
    }

    public function revenue()
    {
        return $this->total() - $this->totalInternalCost();
    }

    public function revenue_str()
    {
        return FinanceHelper::instance()->format_money($this->revenue(), $this->currency);
    }

    public function paid_str()
    {
        return FinanceHelper::instance()->format_money(round($this->paid, 2), $this->currency);
    }

    public function business_paid_str()
    {
        $total = CurrenciesExchange::RateToday(round($this->paid, 2), $this->currency, AdminSettings::GetValue('business_currency', 2));
        return FinanceHelper::instance()->format_money($total, AdminSettings::GetValue('business_currency', 2));
    }

    public function bill_invoice()
    {
        DB::transaction(function () {

            if ($this->status == 'paid') {
                return;
            }

            $this->calculate_cost();

            if (!empty($this->project_id)) {
                $client = User::find($this->user_id);
                $project = \App\Models\Project::find($this->project_id);
                $transaction_id = $project->add_balance(-1 * $this->unpaid_total(), 'Invoice #' . $this->id, 'used', $this->currency);
                $this->transactions()->attach($transaction_id);
                $this->user->calc_ref($this->unpaid_total(), $this->id, $this->currency);
            } else {
                $client = User::find($this->user_id);
                $transaction_id = $client->add_balance(-1 * $this->unpaid_total(), 'Invoice #' . $this->id, 'used', $this->currency);

                $this->transactions()->attach($transaction_id);
                $this->user->calc_ref($this->unpaid_total(), $this->id, $this->currency);
            }
            $this->paid = $this->total();
            $this->unpaid = 0;

            $this->status = 'paid';
            $this->job_status = 'done';
            $this->clearSchedule();
            $this->save();

            $this->refresh();
            $this->calculate_cost();

            $coins = max(1, CurrenciesExchange::RateToday($this->paid, $this->currency, 1) * 10);

            ActionHelper::add_action_coins($this->user, 'Invoice Paid', $coins);

            event(new InvoicePaid($this->user, $this));

            // إضافة جنيه واحد تلقائياً لعداد الخير عند دفع الفاتورة
            $this->addCharityAmount();

            // Check and apply vouchers after invoice payment
            // Get the transaction that was created for this invoice payment
            $paymentTransaction = Transaction::find($transaction_id);
            if ($paymentTransaction && $client) {
                try {
                    $voucherService = new VoucherService();
                    $voucherService->checkAndApplyVouchers($client, $paymentTransaction);
                } catch (\Exception $e) {
                    // Log error but don't fail the transaction
                    \Log::error('Voucher check failed for invoice payment', [
                        'user_id' => $client->id,
                        'invoice_id' => $this->id,
                        'transaction_id' => $transaction_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    public function max_partially_bill_amount()
    {
        if ($this->status == 'partially_paid') {
            return round(min($this->total() - $this->paid, $this->unpaid), 2);
        } else {
            return $this->total();
        }
    }

    /**
     * Post a single direct (client) cost line to cost_transactions immediately.
     * Use when the expense was already paid in real life (e.g. Visa) so the transaction date matches reality.
     * Skipped lines are still processed on full invoice pay in calculate_cost().
     *
     * @return array{ok: bool, cost_transaction_id?: int, message?: string}
     */
    public function postDirectCostLineNow(int $lineId): array
    {
        if (! in_array($this->status, ['unpaid', 'partially_paid', 'paid'], true)) {
            return ['ok' => false, 'message' => __('payment.cost_line_record_invalid_status')];
        }

        $line = $this->costLines()->whereKey($lineId)->first();
        if (! $line || $line->line_type !== 'direct') {
            return ['ok' => false, 'message' => __('payment.cost_line_record_not_found')];
        }
        if ((float) $line->amount <= 0) {
            return ['ok' => false, 'message' => __('payment.cost_line_record_zero')];
        }
        if ($line->cost_transaction_id) {
            return ['ok' => false, 'message' => __('payment.cost_line_already_recorded')];
        }

        return DB::transaction(function () use ($line) {
            $reason = trim((string) $line->description) !== ''
                ? $line->description
                : ('Invoice #' . $this->id . ' — direct cost');
            $c_id = $this->user->add_cost_balance(
                CurrenciesExchange::RateToday((float) $line->amount, $this->currency, $this->client->currency),
                $reason,
                $this->client->currency,
                $this->project_id
            );
            if (! $c_id) {
                return ['ok' => false, 'message' => __('payment.cost_line_record_failed')];
            }
            $line->update(['cost_transaction_id' => $c_id]);
            if (! DB::table('cost_transaction_invoice')
                ->where('invoice_id', $this->id)
                ->where('cost_transaction_id', $c_id)
                ->exists()) {
                $this->cost_transactions()->attach($c_id);
            }

            return ['ok' => true, 'cost_transaction_id' => (int) $c_id];
        });
    }

    public function calculate_cost()
    {
        if ($this->cost_calculated != '0') {
            return;
        }

        $hasLines = $this->costLines()->exists();

        if ($hasLines) {
            if ($this->status !== 'paid') {
                return;
            }
            foreach ($this->costLines()->orderBy('sort_order')->get() as $line) {
                if ($line->line_type === 'direct' && (float) $line->amount > 0 && ! $line->cost_transaction_id) {
                    $reason = trim((string) $line->description) !== ''
                        ? $line->description
                        : ('Invoice #' . $this->id . ' — direct cost');
                    $c_id = $this->user->add_cost_balance(
                        CurrenciesExchange::RateToday((float) $line->amount, $this->currency, $this->client->currency),
                        $reason,
                        $this->client->currency,
                        $this->project_id
                    );
                    $line->update(['cost_transaction_id' => $c_id]);
                    $this->cost_transactions()->attach($c_id);
                }
                if ($line->line_type === 'user_credit' && (float) $line->amount > 0 && $line->credit_user_id && ! $line->earned_transaction_id) {
                    $payee = User::find($line->credit_user_id);
                    if ($payee) {
                        $reason = trim((string) $line->description) !== ''
                            ? ('Invoice #' . $this->id . ': ' . $line->description)
                            : ('Invoice #' . $this->id . ' — cost credit');
                        $tid = $payee->add_balance(
                            (float) $line->amount,
                            $reason,
                            'earned',
                            (int) $this->currency
                        );
                        $line->update(['earned_transaction_id' => $tid]);
                    }
                }
            }
            $totalCost = (float) $this->costLines()->sum('amount');
            $this->update([
                'cost' => $totalCost,
                'cost_calculated' => '1',
                'cost_payable_user_id' => null,
            ]);

            return;
        }

        if ($this->cost_payable_user_id && $this->cost > 0) {
            if ($this->status !== 'paid') {
                return;
            }
            $payee = User::find($this->cost_payable_user_id);
            if ($payee) {
                $payee->add_balance(
                    (float) $this->cost,
                    'Invoice #' . $this->id . ' cost',
                    'earned',
                    (int) $this->currency
                );
            }
            $this->update(['cost_calculated' => '1']);

            return;
        }

        if ($this->cost > 0) {
            $c_id = $this->user->add_cost_balance(
                CurrenciesExchange::RateToday($this->cost, $this->currency, $this->client->currency),
                'Costs for Invoice #' . $this->id,
                $this->client->currency,
                $this->project_id
            );

            $this->cost_transactions()->attach($c_id);
        }
        $this->update(['cost_calculated' => '1']);
    }


    public function partially_bill_invoice($paid)
    {
        if ($this->total() < $paid) {
            return false;
        }
        if (($this->status == 'partially_paid') && (round($this->unpaid, 2) < $paid)) {
            return false;
        }
        DB::transaction(function () use ($paid) {

            $this->calculate_cost();

            if (!empty($this->project_id)) {
                $project = \App\Models\Project::find($this->project_id);
                $transaction_id = $project->add_balance(-1 * $paid, 'Invoice #' . $this->id, 'used', $this->currency);
            } else {

                $client = User::find($this->user_id);
                $transaction_id = $client->add_balance(-1 * $paid, 'Invoice #' . $this->id, 'used', $this->currency);
            }
            $this->transactions()->attach($transaction_id);
            $this->user->calc_ref($paid, $this->id, $this->currency);

            $this->status = 'partially_paid';
            $this->paid += round($paid, 2);
            $this->unpaid = round($this->total() - $this->paid, 2);
            if ($this->job_status == 'pending') {
                $this->job_status = 'processing';
            }
            $this->save();

            if (round((float) $this->paid, 2) == round($this->total(), 2)) {
                $this->status = 'paid';
                $this->job_status = 'done';
                $this->clearSchedule();
                $this->save();

                // إضافة جنيه واحد تلقائياً لعداد الخير عند اكتمال دفع الفاتورة
                $this->addCharityAmount();

                $this->refresh();
                $this->calculate_cost();
            }
        });
    }


    public function delete_with_balance()
    {
        DB::transaction(function () {

            $this->delete_transactions();

            $this->delete();
        });
    }

    public function delete_transactions()
    {
        foreach ($this->transactions()->get() as $transaction) {
            $transaction->delete();
        }
        foreach ($this->cost_transactions()->get() as $cost_transaction) {
            $cost_transaction->delete();
        }
    }

    /**
     * إضافة جنيه واحد تلقائياً لعداد الخير عند دفع الفاتورة
     */
    /**
     * Get the comments for the invoice.
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get the scheduling information from comments.
     */
    public function getSchedule()
    {
        // Find the latest comment that looks like a schedule configuration
        // We look for a special prefix or structure.
        // Assuming we store it as JSON with a specific key.
        $comment = $this->comments()
            ->where('comment', 'like', '{"type":"schedule"%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($comment) {
            return json_decode($comment->comment, true);
        }
        
        // Default: No schedule (Due immediately / created_at)
        return null;
    }

    /**
     * Whether this invoice represents a scheduled job (e.g. from client todo payment).
     * Used for WhatsApp to send "job scheduled successfully" instead of "job done".
     */
    public function isScheduledJob(): bool
    {
        $schedule = $this->schedule;
        return is_array($schedule) && !empty($schedule['end_date']);
    }

    private function addCharityAmount()
    {
        try {
            $charityCounter = CharityCounter::getOrCreateForUser($this->user_id);
            $description = "إضافة تلقائية من دفع الفاتورة رقم: " . $this->enc_id();

            $charityCounter->addAmount(
                1.0, // جنيه واحد
                $description,
                'invoice_payment',
                (string) $this->id
            );
        } catch (\Exception $e) {
            // تسجيل الخطأ إذا حدث ولكن لا نوقف العملية
            \Log::error('خطأ في إضافة المبلغ لعداد الخير: ' . $e->getMessage(), [
                'invoice_id' => $this->id,
                'user_id' => $this->user_id
            ]);
        }
    }

    public function clearSchedule()
    {
        $this->comments()
            ->where('comment', 'like', '{"type":"schedule"%')
            ->delete();
    }
}
