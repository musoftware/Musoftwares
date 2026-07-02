<?php

namespace App\Models;

use App\Helpers\TextHelper;
use App\Helpers\TimerHelper;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($project) {
            if (empty($project->share_token)) {
                $project->share_token = \Illuminate\Support\Str::random(32);
            }
        });
    }

    protected $guarded = ['id'];

    protected $casts = [
        'show_on_landing_portfolio' => 'boolean',
        'portfolio_tech' => 'array',
        'portfolio_gallery' => 'array',
        'hide_future_tasks' => 'boolean',
        'archived' => 'boolean',
        'archived_at' => 'datetime',
        'budget' => 'decimal:3',
        'project_balance' => 'decimal:3',
        'total_paid' => 'decimal:3',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
    ];

    private $oneTime = false;

    /**
     * Projects promoted to the corporate landing portfolio (react-home).
     */
    public function scopeLandingPortfolio($query)
    {
        return $query
            ->where('show_on_landing_portfolio', true)
            ->orderBy('portfolio_sort_order')
            ->orderBy('id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function todos()
    {
        return $this->hasMany(Todo::class, 'project_id');
    }

    public function reports()
    {
        return $this->hasMany(ProjectReport::class, 'project_id');
    }

    public function publishedReports()
    {
        return $this->hasMany(ProjectReport::class, 'project_id')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class, 'project_id');
    }

    public function comments()
    {
        return $this->hasMany(ProjectComment::class, 'project_id');
    }

    public function boardNotes()
    {
        return $this->hasMany(ProjectBoardNote::class, 'project_id');
    }

    public function boardItems()
    {
        return $this->hasMany(ProjectBoardItem::class, 'project_id');
    }

    /* public function swimlanes()
    {
        return $this->hasMany(TodoSwimlane::class, 'project_id');
    } */

    public function hour_rate_client()
    {
        if ($this->client->currency == 2) {
            $bank = CurrenciesExchange::RateToday($this->hour_rate, 1, $this->client->currency);
            $item = GoldWorldPrice::query()
                ->select(DB::raw('DATE(price_date) as price_date, avg(price_24k) as price_24k, avg(price_22k) as price_22k, avg(price_21k) as price_21k, avg(price_18k) as price_18k, avg(price_14k) as price_14k'))
                ->groupBy(DB::raw('DATE(price_date)'))
                ->orderBy(DB::raw('DATE(price_date)'), 'desc')
                ->first();
            $usdPrice1 = CurrenciesExchange::RateByDate($item->price_date, $item->price_21k, 2, 1);
            $price_21 = GoldPrice::query()->where(DB::raw('DATE(price_date)'), $item->price_date)->select(DB::raw('avg(price_21k) as price_21k'))->groupBy(DB::raw('DATE(price_date)'))->first();

            return ((float) $bank + ($this->hour_rate * ($price_21->price_21k / $usdPrice1))) / 2;
        }

        return CurrenciesExchange::RateToday($this->hour_rate, 1, $this->client->currency);
    }

    public function work_time_diff()
    {
        $date_start = Carbon::parse($this->date_start);
        $date_end = Carbon::parse($this->date_end);

        return $date_start->diffForHumans($date_end, CarbonInterface::DIFF_ABSOLUTE, true);
    }

    public function client_balance()
    {
        return $this->hasMany(Transaction::class);
    }

    public function add_balance($amount, $reason, $type, $invoice_id, $currency = null)
    {
        $this->client->add_balance($amount, $reason, $type, $invoice_id, $currency, $this);

        //        $client_balance = new Transaction();
        //        $client_balance->user_id = $this->user_id;
        //        $client_balance->project_id = $this->id;
        //        $client_balance->amount = $amount;
        //        $client_balance->type = $type;
        //        if (!empty($reason)) {
        //            $client_balance->reason = $reason;
        //        }
        //        if ($currency == null) {
        //            $client_balance->currency = $this->currency;
        //        } else {
        //            $client_balance->currency = $currency;
        //        }
        //        $client_balance->invoice_id = $invoice_id;
        //
        //        $client_balance->save();
        //
        //        $this->change_balance($currency);
        //
        //        $first_date = Carbon::now()->toDateTimeString();
        //        $last_date = Carbon::now()->toDateTimeString();
        //        TimerHelper::instance()->updateClientDate($this->user()->first(), $first_date, $last_date);
        //        TimerHelper::instance()->updateProjectDate($this, $first_date, $last_date);
    }

    //    public function change_balance($currency = null)
    //    {
    //        $this->generate_finance($currency, true, true);
    //    }
    public function get_currency()
    {
        // users.currency was renamed to users.currency_id (see 2026_05_24_000001).
        // The User model exposes a `currency` accessor that resolves to currency_id; we still
        // read the real column first and fall back to the legacy attribute for safety.
        $user = $this->user ?? $this->client;
        if ($user) {
            return $user->currency_id ?? $user->currency ?? null;
        }

        return null;
    }

    //    public function generate_finance($currency = null, $force_calculate = false, $force_create = false)
    //    {
    //        if (empty($currency)) {
    //            $currency = $this->get_currency();
    //        }
    //
    //        $balance = $this->client_balance()
    //            ->where('currency', $currency)
    //            ->sum('amount');
    //
    //
    //        $total_paid = $this->client_balance()
    //            ->whereIn('type', ['received', 'sent', 'refunded'])
    //            ->where('currency', $currency)
    //            ->sum('amount');
    //
    //
    //        $finance = $this->ProjectFinanceSummaries()->where('currency', $currency);
    //        if ($finance->count() == 0) {
    //            $finance_row = new \App\Models\ProjectFinanceSummary();
    //        } else {
    //            $finance_row = $finance->first();
    //        }
    //
    //        $finance_row->currency = $currency;
    //        $finance_row->amount = $balance;
    //        $finance_row->total_paid = $total_paid;
    //
    //        if ($force_create || (($balance) != 0)) {
    //            $finance = $this->ProjectFinanceSummaries()->where('currency', $currency);
    //            if ($finance->count() == 0) {
    //                $this->ProjectFinanceSummaries()->save($finance_row);
    //            } else {
    //                $finance_row->save();
    //            }
    //        }
    //    }

    //    public function financeSummariesRemaining(): array
    //    {
    //        $new_items = [];
    //        foreach ($this->ProjectFinanceSummaries()->get() as $key => $finance) {
    //            if (round($finance->amount, 3) != 0) {
    //                $new_items[$key] = $finance;
    //            }
    //        }
    //        return $new_items;
    //    }

    //    public function force_calc($force)
    //    {
    //        foreach (\App\Models\Currency::as_array() as $item) {
    //            $this->generate_finance($item['id'], $force);
    //        }
    //    }

    public function date_start_str()
    {
        return Carbon::parse($this->date_start)->format('Y-m-d');
    }

    public function currencySymbol()
    {
        return optional($this->currencyRow())->symbol;
    }

    public function date_end_str()
    {
        return Carbon::parse($this->date_end)->format('Y-m-d');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute()
    {
        return $this->project_name;
    }

    public function invoice_item_timers()
    {
        return $this->hasMany(InvoiceItemTimer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function currencyRow()
    {
        return Currency::find($this->get_currency());
    }

    public function dueInvoices($currency)
    {
        return -1 * abs($this->invoices()->where('currency', $currency)->whereIn('status', ['partially_paid', 'unpaid'])->sum('unpaid'));
    }

    public function work_time()
    {
        $invoices2 = $this->invoice_item_timers();

        $yearly = $invoices2->select(DB::raw('TIMESTAMPDIFF(SECOND,invoice_item_timers.date_start, invoice_item_timers.date_end) AS diff'), DB::raw('YEAR(invoice_item_timers.created_at) as year'), DB::raw('MONTH(invoice_item_timers.created_at) as month'), DB::raw('DAY(invoice_item_timers.created_at) as day'))
            ->orderBy('day')->get();

        $sum = 0;
        foreach ($yearly as $invoice) {
            $sum += round($invoice->diff, 7);
        }

        return TextHelper::secondsToTime($sum);
    }

    public function financeSummariesTotalPaid(): array
    {
        $new_items = [];
        foreach ($this->ProjectFinanceSummaries()->get() as $key => $finance) {
            if (round($finance->total_paid, 3) != 0) {
                $new_items[$key] = $finance;
            }
        }

        return $new_items;
    }

    public function cost_transactions(): HasMany
    {
        return $this->hasMany(CostTransaction::class, 'project_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ProjectAuditLog::class)->latest('created_at');
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === 'all' || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('archived', 0);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('archived', 1);
    }
}
