<?php

namespace App\Services;

use App\Helpers\BalancesHelper;
use App\Helpers\TimerHelper;
use App\Models\CostTransaction;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionService extends BaseService
{
    public function getIncomeTransactions(array $filters)
    {
        $q = Transaction::with(['user', 'project']);

        $this->applyFilters($q, $filters);

        // No type filter — show ALL transaction types so admin sees the full picture
        // (earned, used, received, refunded, send, timer-received, etc.)

        return $q->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(50);
    }

    public function getCostTransactions(array $filters)
    {
        $q = CostTransaction::with(['user', 'project']);

        $this->applyFilters($q, $filters);

        return $q->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(50);
    }

    private function applyFilters($query, array $filters)
    {
        if (! empty($filters['user'])) {
            $query->where('user_id', $filters['user']);
        }
        if (! empty($filters['project'])) {
            $query->where('project_id', $filters['project']);
        }
        if (! empty($filters['currency'])) {
            $query->where('currency_id', $filters['currency']);
        }
        if (! empty($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }
        if (! empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
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
