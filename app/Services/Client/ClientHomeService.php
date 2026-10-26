<?php

namespace App\Services\Client;

use App\Helpers\FinanceHelper;
use App\Models\CurrenciesExchange;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Everything the client home page shows, in the order a client asks about it:
 * what do I owe and where do I pay, what needs me, what do I have, and how am I doing.
 */
class ClientHomeService
{
    private const RENEWAL_WARNING_DAYS = 14;

    public function __construct(
        private readonly LoyaltyService $loyalty,
        private readonly MonthlyLeaderboardService $leaderboard,
    ) {}

    public function build(User $user): array
    {
        $invoices = $this->unpaidInvoices($user);
        $subscriptions = UserSubscription::where('user_id', $user->id)->where('status', 'active')->orderBy('expires_at')->get();

        return [
            'money' => $this->money($user, $invoices, $subscriptions),
            'attention' => $this->attention($user, $invoices, $subscriptions),
            'services' => $this->services($user, $subscriptions),
            'rewards' => $this->rewards($user),
        ];
    }

    /** @return Collection<int, Invoice> open invoices the client can pay now, oldest due first */
    private function unpaidInvoices(User $user): Collection
    {
        return $user->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->where('is_suspended', false)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->unpaid_total() > 0)
            ->sortBy(fn (Invoice $invoice) => $invoice->due_date ?? $invoice->created_at?->toDateString())
            ->values();
    }

    private function money(User $user, Collection $invoices, Collection $subscriptions): array
    {
        $total = 0.0;
        foreach ($invoices as $invoice) {
            $total += $this->inUserCurrency($invoice->unpaid_total(), $invoice->currency_id, $user);
        }

        $next = $invoices->first();
        $renewal = $subscriptions->first(fn (UserSubscription $s) => $s->expires_at !== null);

        return [
            'total_due' => round($total, 2),
            'total_due_formatted' => $this->format($total, $user->currency_id),
            'unpaid_count' => $invoices->count(),
            'overdue_count' => $invoices->filter(fn (Invoice $i) => $this->isOverdue($i))->count(),
            'next_invoice' => $next ? $this->invoiceRow($next) : null,
            'wallet_formatted' => $this->format((float) ($user->user_balance ?? 0), $user->currency_id),
            'next_renewal' => $renewal ? [
                'name' => $this->systemName($renewal->object),
                'date' => $renewal->expires_at->toDateString(),
                'days_left' => (int) now()->startOfDay()->diffInDays($renewal->expires_at->copy()->startOfDay(), false),
                'auto_renew' => (bool) $renewal->auto_renew,
            ] : null,
            'links' => [
                'invoices' => route('billing.invoices.index'),
                'add_balance' => url('/financial/add-balance'),
                'subscriptions' => url('/subscriptions/manage'),
            ],
        ];
    }

    /** Short to-do list: only things the client can act on, most urgent first. */
    private function attention(User $user, Collection $invoices, Collection $subscriptions): array
    {
        $items = [];

        foreach ($invoices->take(3) as $invoice) {
            $row = $this->invoiceRow($invoice);
            $items[] = [
                'type' => $row['is_overdue'] ? 'invoice_overdue' : 'invoice_due',
                'amount' => $row['amount_formatted'],
                'number' => $row['number'],
                'date' => $row['due_date'],
                'url' => $row['pay_url'],
            ];
        }

        foreach ($subscriptions as $subscription) {
            if (! $subscription->expires_at || $subscription->auto_renew) {
                continue;
            }
            $days = (int) now()->startOfDay()->diffInDays($subscription->expires_at->copy()->startOfDay(), false);
            if ($days <= self::RENEWAL_WARNING_DAYS) {
                $items[] = ['type' => 'renewal', 'name' => $this->systemName($subscription->object), 'days' => max(0, $days), 'url' => url('/subscriptions/manage')];
            }
        }

        $unread = $user->unreadNotifications()->count();
        if ($unread > 0) {
            $items[] = ['type' => 'notifications', 'count' => $unread, 'url' => url('/notifications')];
        }

        $completion = (int) ($user->profile_completion_percentage ?? 100);
        if ($completion < 100) {
            $items[] = ['type' => 'profile', 'percent' => $completion, 'url' => route('profile.edit')];
        }

        return $items;
    }

    private function services(User $user, Collection $subscriptions): array
    {
        $projects = Project::where('user_id', $user->id)->where('archived', 0)->latest()->take(3)->get();

        return [
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->project_name,
                'progress' => (int) ($p->progress_percentage ?? 0),
                'url' => url('/projects/'.$p->id),
            ])->all(),
            'projects_count' => Project::where('user_id', $user->id)->where('archived', 0)->count(),
            'subscriptions' => $subscriptions->map(fn (UserSubscription $s) => [
                'name' => $this->systemName($s->object),
                'expires_at' => $s->expires_at?->toDateString(),
            ])->all(),
        ];
    }

    private function rewards(User $user): array
    {
        $summary = $this->loyalty->getUserSummary($user);
        $tier = strtolower($summary['current_tier']?->slug ?? $summary['tier'] ?? 'bronze');
        $next = $summary['next_tier'];

        return [
            'tier' => $tier,
            'tier_name' => $this->translated('client_home.tiers.'.$tier, $summary['current_tier']?->name ?? Str::headline($tier)),
            'balance' => $summary['balance'],
            'lifetime_points' => $summary['lifetime_points'],
            'progress' => $summary['progress_percentage'],
            'next_tier_name' => $next ? $this->translated('client_home.tiers.'.strtolower($next->slug), $next->name) : null,
            'points_to_next' => $summary['points_to_next_tier'],
            'monthly' => $this->leaderboard->forUser($user, 5),
        ];
    }

    private function invoiceRow(Invoice $invoice): array
    {
        return [
            'number' => (string) $invoice->id,
            'amount_formatted' => FinanceHelper::instance()->format_money($invoice->unpaid_total(), $invoice->currency_id),
            'due_date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->toDateString() : null,
            'is_overdue' => $this->isOverdue($invoice),
            'pay_url' => route('billing.invoices.pay', $invoice->uuid),
        ];
    }

    private function isOverdue(Invoice $invoice): bool
    {
        return $invoice->due_date !== null && Carbon::parse($invoice->due_date)->endOfDay()->isPast();
    }

    private function inUserCurrency(float $amount, ?int $currencyId, User $user): float
    {
        if (! $currencyId || ! $user->currency_id || $currencyId === (int) $user->currency_id) {
            return $amount;
        }

        try {
            return (float) CurrenciesExchange::RateToday($amount, $currencyId, $user->currency_id);
        } catch (\Throwable) {
            return $amount; // a missing rate must not break the home page
        }
    }

    private function format(float $amount, ?int $currencyId): string
    {
        return FinanceHelper::instance()->format_money($amount, $currencyId ?: 1);
    }

    private function systemName(?string $object): string
    {
        return $this->translated('client_home.systems.'.$object, Str::headline((string) $object));
    }

    private function translated(string $key, string $fallback): string
    {
        $text = __($key);

        return is_string($text) && $text !== $key ? $text : $fallback;
    }
}
