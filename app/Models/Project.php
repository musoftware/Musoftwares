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
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($project) {
            if (empty($project->share_token)) {
                $project->share_token = Str::random(32);
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
        'ai_enabled' => 'boolean',
        'ai_understanding_pct' => 'integer',
        'ai_stage' => 'string',
        'ai_context' => 'array',
        'ai_summary' => 'array',
        'ai_questions' => 'array',
        'ai_actions_log' => 'array',
        'last_ai_charged_at' => 'datetime',
    ];

    public function getAiStageAttribute($value)
    {
        return $value ?: 'greeting';
    }

    public function getAiContextAttribute($value)
    {
        $default = [
            'current_goal'           => null,
            'current_stage'          => 'greeting',
            'completed_features'     => [],
            'pending_features'       => [],
            'current_invoice_status' => 'none',
            'current_invoice_id'     => null,
            'tech_stack'             => [],
            'developer_notes'        => null,
            'known_decisions'        => [],
        ];

        if (empty($value)) {
            return $default;
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return array_merge($default, is_array($decoded) ? $decoded : []);
    }

    public function updateAiContext(array $updates): void
    {
        $current = $this->ai_context;
        foreach ($updates as $k => $v) {
            if (is_array($v) && isset($current[$k]) && is_array($current[$k])) {
                // Merge array fields without duplicating items, capped to max 50 items
                $merged = array_values(array_unique(array_merge($current[$k], $v), SORT_REGULAR));
                $current[$k] = array_slice($merged, -50);
            } else {
                $current[$k] = $v;
            }
        }
        $this->ai_context = $current;
        if ($this->exists) {
            $this->save();
        }
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProjectShare::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function todos(): HasMany
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

    public function adminNotes()
    {
        return $this->hasMany(ProjectAdminNote::class, 'project_id');
    }

    public function boardItems()
    {
        return $this->hasMany(ProjectBoardItem::class, 'project_id');
    }

    public function boardCategories()
    {
        return $this->hasMany(ProjectBoardCategory::class, 'project_id');
    }

    /* public function swimlanes()
    {
        return $this->hasMany(TodoSwimlane::class, 'project_id');
    } */

    public function hour_rate_client()
    {
        $clientCurrency = $this->client?->currency ?? 1;

        return CurrenciesExchange::RateToday($this->hour_rate, 1, $clientCurrency);
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

    public function add_balance($amount, $reason, $type, ?int $currencyId = null, $createdAt = null)
    {
        return $this->client->add_balance($amount, $reason, $type, $currencyId, $this, $createdAt);
    }
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

    public function getNameAttribute()
    {
        return $this->project_name ?? ($this->attributes['name'] ?? null);
    }

    public function invoice_item_timers()
    {
        return $this->hasMany(InvoiceItemTimer::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function activeInvoice()
    {
        return $this->invoices()->where('status', '!=', 'cancelled')->latest()->first();
    }

    public function currencyRow()
    {
        return Currency::find($this->get_currency());
    }

    public function dueInvoices($currency)
    {
        return -1 * abs($this->invoices()->where('currency', $currency)->whereIn('status', ['partially_paid', 'unpaid'])->sum('unpaid'));
    }

    /**
     * Resolve this project's own currency id (falls back to the client's currency).
     * Used as the target currency for all per-project financial aggregations.
     */
    public function resolveCurrencyId(): ?int
    {
        return $this->get_currency();
    }

    /**
     * Total COST incurred on this project (sum of cost_transactions),
     * converted into the project currency. This is real spend, not a cached slice.
     */
    public function costAmount(): float
    {
        $currencyId = $this->resolveCurrencyId();
        $total = 0;
        foreach ($this->costTransactions()->groupBy('currency_id')
            ->selectRaw('sum(amount) as total_amount, currency_id')
            ->get() as $item) {
            if ($item->total_amount == 0) {
                continue;
            }
            $total += (float) CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $currencyId);
        }

        return (float) $total;
    }

    /**
     * Total amount PAID against this project's invoices,
     * converted into the project currency.
     */
    public function paidInvoicesAmount(): float
    {
        $currencyId = $this->resolveCurrencyId();
        $total = 0;
        foreach ($this->invoices()->groupBy('currency_id')
            ->selectRaw('sum(paid) as total_amount, currency_id')
            ->get() as $item) {
            if ($item->total_amount == 0) {
                continue;
            }
            $total += (float) CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $currencyId);
        }

        return (float) $total;
    }

    /**
     * Total OUTSTANDING (pending/unpaid) amount on this project's invoices,
     * converted into the project currency. Only unpaid/partially_paid invoices count.
     */
    public function pendingInvoicesAmount(): float
    {
        $currencyId = $this->resolveCurrencyId();
        $total = 0;
        foreach ($this->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->groupBy('currency_id')
            ->selectRaw('sum(unpaid) as total_amount, currency_id')
            ->get() as $item) {
            if ($item->total_amount == 0) {
                continue;
            }
            $total += (float) CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $currencyId);
        }

        return (float) $total;
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

    public function costTransactions(): HasMany
    {
        return $this->cost_transactions();
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

    /**
     * Ensure the AI fee is charged for today.
     * Returns true if successfully charged (or already charged today).
     * Returns false if user has insufficient balance, which deactivates AI.
     */
    public function ensureAiIsCharged(\App\Models\User $user): bool
    {
        if (!$this->ai_enabled) {
            return false;
        }

        $today = Carbon::now('Africa/Cairo')->toDateString();

        if ($this->last_ai_charged_at) {
            $chargedDate = Carbon::parse($this->last_ai_charged_at)->timezone('Africa/Cairo')->toDateString();
            if ($chargedDate === $today) {
                return true;
            }
        }

        // Charge 10 EGP
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        if (!$egpCurrency) {
            return false;
        }

        $costInUserCurrency = \App\Models\CurrenciesExchange::RateToday(10.0, $egpCurrency->id, $user->currency_id);

        if ((float) $user->user_balance < $costInUserCurrency) {
            // Deactivate AI
            $this->update([
                'ai_enabled' => false,
            ]);

            // Add system comment to project board informing the client
            $this->comments()->create([
                'project_id' => $this->id,
                'author_id' => null,
                'guest_name' => 'System',
                'body' => '[System: AI Project Manager deactivated due to insufficient wallet balance to cover the daily fee (10 EGP).]',
                'commentable_type' => self::class,
                'commentable_id' => $this->id,
            ]);

            return false;
        }

        DB::transaction(function () use ($user, $costInUserCurrency) {
            // Deduct
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'amount' => -$costInUserCurrency,
                'reason' => 'Daily AI Project Manager Fee for project: ' . $this->project_name,
                'category' => 'other',
                'type' => 'used',
                'project_id' => $this->id,
                'currency_id' => $user->currency_id,
            ]);

            $this->update([
                'last_ai_charged_at' => Carbon::now('Africa/Cairo'),
            ]);

            \App\Helpers\BalancesHelper::UpdateBalance($user, $this);
        });

        return true;
    }
}
