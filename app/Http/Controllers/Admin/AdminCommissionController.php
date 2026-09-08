<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\BalancesHelper;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Earning;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminCommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $cairoNow = Carbon::now('Africa/Cairo')->toDateString();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', 'all'),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => (string) $request->query('sort_by', 'convert_to_balance_on'),
            'direction' => strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            'per_page' => (int) $request->query('per_page', 20),
        ];

        if (! in_array($filters['per_page'], [10, 20, 50, 100])) {
            $filters['per_page'] = 20;
        }

        $query = Earning::query()
            ->with([
                'user:id,name,email,avatar_url',
                'referred_user:id,name,email,avatar_url',
                'currencyModel:id,currency,symbol',
                'invoice:id,invoice_number,date',
            ]);

        $this->applySearchFilter($query, $filters['search']);
        $this->applyStatusFilter($query, $filters['status'], $cairoNow);
        $this->applyDateFilter($query, $filters['date_from'], $filters['date_to']);

        $allowedSorts = ['convert_to_balance_on', 'created_at', 'amount'];
        $sortColumn = in_array($filters['sort_by'], $allowedSorts) ? $filters['sort_by'] : 'convert_to_balance_on';
        $query->orderBy($sortColumn, $filters['direction']);

        $paginated = $query->paginate($filters['per_page'])->withQueryString();

        $transformedItems = $paginated->getCollection()->map(function (Earning $earning) use ($cairoNow) {
            $ctb = $earning->convert_to_balance_on ? Carbon::parse($earning->convert_to_balance_on)->format('Y-m-d') : null;
            $status = $earning->transaction_id ? 'cleared' : ($ctb && $ctb <= $cairoNow ? 'due' : 'pending');

            $daysDiff = null;
            if ($ctb) {
                $daysDiff = (int) Carbon::parse($cairoNow)->diffInDays(Carbon::parse($ctb), false);
            }

            return [
                'id' => $earning->id,
                'user' => $earning->user ? [
                    'id' => $earning->user->id,
                    'name' => $earning->user->name,
                    'email' => $earning->user->email,
                    'avatar_url' => $earning->user->avatar_url,
                ] : null,
                'referred_user' => $earning->referred_user ? [
                    'id' => $earning->referred_user->id,
                    'name' => $earning->referred_user->name,
                    'email' => $earning->referred_user->email,
                    'avatar_url' => $earning->referred_user->avatar_url,
                ] : null,
                'referred_invoice_id' => $earning->referred_invoice_id,
                'invoice_number' => $earning->invoice?->invoice_number,
                'amount' => (float) $earning->amount,
                'currency' => $earning->currencyModel?->currency ?? 'EGP',
                'convert_to_balance_on' => $ctb,
                'days_remaining' => $daysDiff,
                'status' => $status,
                'transaction_id' => $earning->transaction_id,
                'created_at' => $earning->created_at?->format('Y-m-d H:i'),
            ];
        });

        $paginated->setCollection($transformedItems);

        $stats = $this->calculateStats($cairoNow);

        return Inertia::render('Admin/Commissions/Index', [
            'commissions' => $paginated,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function clearNow(Request $request, Earning $earning): RedirectResponse
    {
        if ($earning->transaction_id) {
            return redirect()->back()->with('error', __('admin.commission_already_cleared'));
        }

        if (! $earning->user) {
            return redirect()->back()->with('error', __('admin.commission_missing_user'));
        }

        DB::transaction(function () use ($earning) {
            $description = $earning->referred_invoice_id
                ? __('admin.referral_commission_invoice', ['id' => $earning->referred_invoice_id])
                : __('admin.referral_commission');

            $txId = $earning->user->add_balance(
                $earning->amount,
                $description,
                'earned',
                $earning->currency_id,
                null,
                now('Africa/Cairo')
            );

            $earning->transaction_id = $txId;
            $earning->save();

            if (class_exists(BalancesHelper::class)) {
                BalancesHelper::UpdateBalance($earning->user, null);
            }
        });

        return redirect()->back()->with('success', __('admin.commission_cleared_successfully'));
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->whereHas('user', function (Builder $uq) use ($search) {
                $uq->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            })
            ->orWhereHas('referred_user', function (Builder $rq) use ($search) {
                $rq->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            })
            ->orWhere('referred_invoice_id', 'LIKE', "%{$search}%");
        });
    }

    private function applyStatusFilter(Builder $query, string $status, string $cairoNow): void
    {
        if ($status === 'pending') {
            $query->whereNull('transaction_id')
                ->where('convert_to_balance_on', '>', $cairoNow);
        } elseif ($status === 'due') {
            $query->whereNull('transaction_id')
                ->where('convert_to_balance_on', '<=', $cairoNow);
        } elseif ($status === 'cleared') {
            $query->whereNotNull('transaction_id');
        }
    }

    private function applyDateFilter(Builder $query, string $from, string $to): void
    {
        if ($from !== '') {
            $query->where('convert_to_balance_on', '>=', $from);
        }
        if ($to !== '') {
            $query->where('convert_to_balance_on', '<=', $to);
        }
    }

    private function calculateStats(string $cairoNow): array
    {
        $pendingCount = Earning::query()
            ->whereNull('transaction_id')
            ->where('convert_to_balance_on', '>', $cairoNow)
            ->count();

        $dueCount = Earning::query()
            ->whereNull('transaction_id')
            ->where('convert_to_balance_on', '<=', $cairoNow)
            ->count();

        $clearedCount = Earning::query()
            ->whereNotNull('transaction_id')
            ->count();

        $nextUpcoming = Earning::query()
            ->whereNull('transaction_id')
            ->where('convert_to_balance_on', '>=', $cairoNow)
            ->orderBy('convert_to_balance_on', 'asc')
            ->value('convert_to_balance_on');

        return [
            'pending_count' => $pendingCount,
            'due_count' => $dueCount,
            'cleared_count' => $clearedCount,
            'total_count' => $pendingCount + $dueCount + $clearedCount,
            'next_upcoming_date' => $nextUpcoming ? Carbon::parse($nextUpcoming)->format('Y-m-d') : null,
        ];
    }
}
