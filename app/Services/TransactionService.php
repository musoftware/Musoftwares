<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\CostTransaction;
use App\Models\User;
use App\Models\Project;
use App\Helpers\TimerHelper;
use App\Helpers\BalancesHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function getIncomeTransactions(array $filters)
    {
        $q = Transaction::with(['user', 'project']);

        $this->applyFilters($q, $filters);
        
        $q->whereIn('type', ['received', 'refunded', 'send']);
        
        return $q->orderBy('created_at', 'desc')->paginate(50);
    }

    public function getCostTransactions(array $filters)
    {
        $q = CostTransaction::with(['user', 'project']);

        $this->applyFilters($q, $filters);
        
        return $q->orderBy('created_at', 'desc')->paginate(50);
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['user'])) {
            $query->where('user_id', $filters['user']);
        }
        if (!empty($filters['project'])) {
            $query->where('project_id', $filters['project']);
        }
        if (!empty($filters['currency'])) {
            $query->where('currency_id', $filters['currency']);
        }
        if (!empty($filters['month'])) {
            $query->whereMonth('created_at', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }
    }

    public function processTransactionBatch(Request $request, User $user, ?Project $project, array $data, string $type): int
    {
        $added = 0;

        DB::transaction(function () use ($request, $user, $project, $data, $type, &$added) {
            foreach ($data as $item) {
                if ($type === 'timer-received' || $type === 'timer-due') {
                    $added += TimerHelper::instance()->addTimerReceived($request, $user, $project, $item);
                } elseif ($type === 'out-timer-received') {
                    $added += TimerHelper::instance()->addNoTimerReceived($request, $user, $project, $item);
                } elseif ($type === 'refund') {
                    $added += TimerHelper::instance()->addRefund($request, $user, $project, $item);
                } elseif ($type === 'earned') {
                    $added += TimerHelper::instance()->addEarned($request, $user, $project, $item);
                } elseif ($type === 'send') {
                    $added += TimerHelper::instance()->addSend($request, $user, $project, $item);
                }
            }

            if ($request->has('unpaid_invoices') && $request->get('unpaid_invoices') === 'true') {
                $user->try_pay_unpaid_invoices();
            }
        });

        return $added;
    }

    public function reverseTransaction(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            return $transaction->createReverse();
        });
    }

    public function recalculateUserBalance(User $user, ?Project $project = null): void
    {
        BalancesHelper::UpdateBalance($user, $project);
    }
}
