<?php

namespace App\Helpers;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalancesHelper
{
    public static function UpdateBalance($user, $project = null)
    {
        BalancesHelper::instance()->CalcBalance($user);
        BalancesHelper::instance()->CalcTotalSpend($user);

        if ($project != null) {
            BalancesHelper::instance()->CalcBalance($project->user, $project);
            BalancesHelper::instance()->CalcTotalSpend($project->user, $project);
        }
    }

    /**
     * @var BalancesHelper
     */
    protected static $instance = null;

    public static function instance(): ?BalancesHelper
    {
        if (self::$instance === null) {
            self::$instance = new BalancesHelper();
        }
        return self::$instance;
    }

    public function CalcWithdrawingCommission($user)
    {
        $commission_amount = $user->withdraw()->whereIn('status', ['pending', 'reviewing'])->sum('amount');
        $user->withdrawing_commission = $commission_amount;
        $user->save();
        return $commission_amount;
    }

    public function CalcWithdrawnCommission($user)
    {
        $data = $user->withdraw()->groupBy('currency_id')->where('status', 'approved')->select(DB::raw('sum(amount) as amount, currency_id'))->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDateNoRound($commission->created_at, $commission->amount, $commission->currency_id, $user->currency_id);
            $amount += $user_amount;
        }
        $user->withdrawn_commission = $amount;
        $user->save();
        return $amount;
    }

    public function CalcPendingCommission($user)
    {
        $data = $user->commissions()->groupBy('currency_id')->where('convert_to_balance_on', '>', DB::raw('NOW()'))->select(DB::raw('sum(amount) as amount, currency_id'))->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDateNoRound($commission->created_at, $commission->amount, $commission->currency_id, $user->currency_id);
            $amount += $user_amount;
        }
        $user->pending_commission = $amount;
        $user->save();
    }

    public function CalcBalance($user, $project = null)
    {
        $balance = $user->transactions()
            ->groupBy('currency_id')
            ->when($project != null, function ($q) use ($project) {
                return $q->where('project_id', $project->id);
            })
            ->select(DB::raw('sum(amount) as total_amount'), 'currency_id')
            ->get();
        $total_spend = 0;
        foreach ($balance as $item) {
            $total_spend += CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $user->currency_id);
        }
        if ($project == null) {
            $user->user_balance = $total_spend - $this->CalcWithdrawingCommission($user);
            $user->save();
        } else {
            $project->project_balance = $total_spend;
            $project->save();
        }
    }

    public function CalcTotalSpend($user, $project = null)
    {
        $total_paid = $user->transactions()
            ->whereIn('type', ['received', 'sent', 'refunded'])
            ->when($project != null, function ($q) use ($project) {
                return $q->where('project_id', $project->id);
            })
            ->groupBy('currency_id')
            ->select(DB::raw('sum(amount) as total_amount'), 'currency_id')
            ->get();

        $total_spend = 0;
        foreach ($total_paid as $item) {
            $total_spend += CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $user->currency_id);
        }

        if ($project == null) {

            $user->total_paid = $total_spend;
            $user->save();
        } else {
            $project->total_paid = $total_spend;
            $project->save();
        }
    }

    public function CalcCostBalance($user)
    {
        $balance = $user->costTransactions()
            ->groupBy('currency_id')
            ->select(DB::raw('sum(amount) as total_amount'), 'currency_id')
            ->get();
        $total_spend = 0;
        foreach ($balance as $item) {
            $total_spend += CurrenciesExchange::RateTodayNoRound($item->total_amount, $item->currency_id, $user->currency_id);
        }
        $user->total_cost = $total_spend;
        $user->save();
    }


    //CalcCostBalance

}
