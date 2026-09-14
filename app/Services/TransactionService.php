<?php

namespace App\Services;

use App\Helpers\BalancesHelper;
use App\Helpers\TimerHelper;
use App\Models\CostTransaction;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

use Carbon\Carbon;

class TransactionService extends BaseService
{
    public function getIncomeTransactions(array $filters)
    {
        $q = Transaction::with(['user', 'project', 'currency']);

        $this->applyFilters($q, $filters);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = strtolower($filters['direction'] ?? $filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'amount', 'business_amount', 'created_at', 'type'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        if ($perPage < 10 || $perPage > 100) {
            $perPage = 50;
        }

        return $q->orderBy($sortField, $sortDir)->orderBy('id', $sortDir)->paginate($perPage);
    }

    public function getCostTransactions(array $filters)
    {
        $q = CostTransaction::with(['user', 'project']);

        $this->applyFilters($q, $filters);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = strtolower($filters['direction'] ?? $filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'amount', 'business_amount', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        if ($perPage < 10 || $perPage > 100) {
            $perPage = 50;
        }

        return $q->orderBy($sortField, $sortDir)->orderBy('id', $sortDir)->paginate($perPage);
    }

    public function getTransactionSummary(array $filters, string $type = 'income'): array
    {
        $model = ($type === 'cost') ? CostTransaction::class : Transaction::class;
        $q = $model::query();
        $this->applyFilters($q, $filters);

        $totalCount = (clone $q)->count();
        $totalBusinessAmount = (clone $q)->sum('business_amount');

        $positiveBusinessAmount = (clone $q)->where('business_amount', '>', 0)->sum('business_amount');
        $negativeBusinessAmount = (clone $q)->where('business_amount', '<', 0)->sum('business_amount');

        $avgBusinessAmount = $totalCount > 0 ? ($totalBusinessAmount / $totalCount) : 0;

        return [
            'total_count' => (int) $totalCount,
            'total_business_amount' => (float) round((float) $totalBusinessAmount, 2),
            'positive_business_amount' => (float) round((float) $positiveBusinessAmount, 2),
            'negative_business_amount' => (float) round(abs((float) $negativeBusinessAmount), 2),
            'avg_business_amount' => (float) round((float) $avgBusinessAmount, 2),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $cleanedId = ltrim(str_replace('#', '', $term), '0');
                if ($cleanedId !== '' && is_numeric($cleanedId)) {
                    $q->orWhere('id', (int) $cleanedId);
                }
                $q->orWhere('reason', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  })
                  ->orWhereHas('project', function ($pq) use ($term) {
                      $pq->where('project_name', 'like', "%{$term}%");
                  });
            });
        }

        if (! empty($filters['tx_type'])) {
            $model = $query->getModel();
            if ($model instanceof Transaction || in_array('type', $model->getFillable())) {
                $query->where('type', $filters['tx_type']);
            }
        }

        if (! empty($filters['user'])) {
            $query->where('user_id', $filters['user']);
        }

        if (! empty($filters['project'])) {
            $query->where('project_id', $filters['project']);
        }

        if (! empty($filters['currency'])) {
            $query->where('currency_id', $filters['currency']);
        }

        if (! empty($filters['from_date'])) {
            try {
                $from = Carbon::parse($filters['from_date'], 'Africa/Cairo')->startOfDay()->setTimezone('UTC');
                $query->where('created_at', '>=', $from);
            } catch (\Throwable $e) {
                // Ignore parse errors
            }
        }

        if (! empty($filters['to_date'])) {
            try {
                $to = Carbon::parse($filters['to_date'], 'Africa/Cairo')->endOfDay()->setTimezone('UTC');
                $query->where('created_at', '<=', $to);
            } catch (\Throwable $e) {
                // Ignore parse errors
            }
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        $hasMin = isset($filters['min_amount']) && is_numeric($filters['min_amount']);
        $hasMax = isset($filters['max_amount']) && is_numeric($filters['max_amount']);

        if ($hasMin && $hasMax) {
            $min = (float) $filters['min_amount'];
            $max = (float) $filters['max_amount'];
            $query->where(function ($q) use ($min, $max) {
                $q->whereBetween('amount', [$min, $max])
                  ->orWhereBetween('amount', [-$max, -$min]);
            });
        } elseif ($hasMin) {
            $min = (float) $filters['min_amount'];
            $query->where(function ($q) use ($min) {
                $q->where('amount', '>=', $min)
                  ->orWhere('amount', '<=', -$min);
            });
        } elseif ($hasMax) {
            $max = (float) $filters['max_amount'];
            $query->where(function ($q) use ($max) {
                $q->whereBetween('amount', [-$max, $max]);
            });
        }
    }

    public function processTransactionBatch(Request $request, User $user, ?Project $project, array $data, string $type): int
    {
        $added = 0;

        $this->executeInTransaction(function () use ($request, $user, $project, $data, $type, &$added) {
            foreach ($data as $item) {
                $itemProject = $project;
                if (! $itemProject && isset($item['project']) && $item['project']) {
                    $itemProject = $user->projects()->find($item['project']);
                }

                if ($type === 'timer-received' || $type === 'timer-due') {
                    $added += TimerHelper::instance()->addTimerReceived($request, $user, $itemProject, $item);
                } elseif ($type === 'out-timer-received') {
                    $added += TimerHelper::instance()->addNoTimerReceived($request, $user, $itemProject, $item);
                } elseif ($type === 'refund') {
                    $added += TimerHelper::instance()->addRefund($request, $user, $itemProject, $item);
                } elseif ($type === 'earned') {
                    $added += TimerHelper::instance()->addEarned($request, $user, $itemProject, $item);
                } elseif ($type === 'send') {
                    $added += TimerHelper::instance()->addSend($request, $user, $itemProject, $item);
                } elseif ($type === 'used') {
                    $added += TimerHelper::instance()->addUsedTransaction($request, $user, $itemProject, $item);
                }
            }

            if ($request->boolean('unpaid_invoices')) {
                $user->try_pay_unpaid_invoices();
            }
        });

        return $added;
    }

    public function reverseTransaction(Transaction $transaction): Transaction
    {
        return $this->executeInTransaction(function () use ($transaction) {
            return $transaction->createReverse();
        });
    }

    public function recalculateUserBalance(User $user, ?Project $project = null): void
    {
        BalancesHelper::UpdateBalance($user, $project);
    }
}
