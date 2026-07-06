<?php

namespace App\Helpers;

use App\Events\AmountReceived;

use App\Models\CostTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimerHelper
{

    /**
     * @var TimerHelper
     */
    protected static $instance = null;

    public static function instance(): ?TimerHelper
    {
        if (self::$instance === null) {
            self::$instance = new TimerHelper();
        }
        return self::$instance;
    }

    public function addTimerReceived(Request $request, $client, $project, $item)
    {
        if ($item['amount'] === 0) return 0;

        $this->addTransaction($request, $client, $project,
            (($request->input('type') == 'timer-received') ? 1 : -1) * abs($item['amount']),
            ($item['reason']), $item['fee'],
            ($request->input('type') == 'timer-received') ? 'received' : 'used',
            isset($item['is_used']) && $item['is_used'] == '1',
            $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null);

//        $client->add_balance((($request->input('type') == 'timer-received') ? 1 : -1) * abs($item['amount']),
//            ($item['reason']),
//            ($request->input('type') == 'timer-received') ? 'received' : 'used'
//            , null, $project);
//
//        $client->add_cost_balance($item['fee'],
//            ($item['reason'] . ' Fee')
//            , null, $project);


//        if (($request->input('type') == 'timer-received')) {
//            if (isset($item['is_used']) && $item['is_used'] == '1') {
//
//                $client->add_balance(-1 * abs($item['amount']),
//                    ($item['reason']), 'used'
//                    , null, $project);
//            }
//        }

        return 1;
    }

    public function addNoTimerReceived(Request $request, $client, $project, $item)
    {
        if ($item['amount'] === 0) return 0;


        $this->addTransaction($request, $client, $project,
            abs($item['amount']),
            ($item['reason']), $item['fee'],
            'received',
            isset($item['is_used']) && $item['is_used'] == '1',
            $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null);


//
//        $client->add_balance(abs($item['amount']),
//            ($item['reason']),
//            'received'
//            , null, $project);
//
//        $client->add_cost_balance($item['fee'],
//            ($item['reason'] . ' Fee')
//            , null, $project);


//        $invoice = new Transaction();
//        $first_date = $invoice->date_start;
//        $last_date = $invoice->date_end;
//        $invoice->amount = abs($item['amount']);
//        if (!empty($item['reason'])) {
//            $invoice->reason = ($item['reason']);
//        }
//        $invoice->type = 'received';
//        $invoice->currency = $client->currency;
//        if ($project) {
//            $invoice->project_id = $project->id;
//        }
//        $client->client_balance()->save($invoice);
//
//        if ($item['fee'] > 0) {
//            $c = new CostTransaction();
//            $c->user_id = $client->id;
//            if ($project) {
//                $c->project_id = $project->id;
//            }
//            $c->amount = $item['fee'];
//            $c->reason = $invoice->reason . ' Fee';
//            $c->currency = $client->currency;
//            $c->save();
//        }
//
//        if (isset($item['is_used']) && $item['is_used'] == 'true') {
//            $invoice2 = new Transaction();
//            $first_date = $first_date ?? $invoice2->date_start;
//            $last_date = $invoice2->date_end;
//            $invoice2->amount = -1 * abs($item['amount']);
//            if (!empty($item['reason'])) {
//                $invoice2->reason = ($item['reason']);
//            }
//            $invoice2->type = 'used';
//            $invoice2->currency = $client->currency;
//            if ($project) {
//                $invoice2->project_id = $project->id;
//            }
//            $client->client_balance()->save($invoice2);
//
//        }
//
//        $this->updateClientDate($client, $first_date, $last_date);
//        $this->updateProjectDate($project, $first_date, $last_date);

        return 1;
    }


    private function addTransaction(Request|null $request, $client, $project, $amount, $reason, $fee, $type, $is_used, $createdAt = null)
    {
        DB::transaction(function () use ($client, $project, $amount, $reason, $fee, $type, $is_used, $createdAt) {
            $client->add_balance($amount,
                $reason,
                $type
                , null, $project, $createdAt);

            \App\Models\CostTransaction::add_cost_balance($client, $fee,
                ($reason . ' Fee')
                , null, $project, $createdAt);

            if ($is_used) {
                $client->add_balance(-1 * abs($amount),
                    $reason . ' Used', 'used'
                    , null, $project, $createdAt);
            }
        });

        // Fire AmountReceived event for WhatsApp notifications when money is received
        if (in_array($type, ['received', 'earned']) && $amount > 0) {
            \Log::info('🔥 Firing AmountReceived Event', [
                'user_id' => $client->id,
                'user_name' => $client->name,
                'amount' => $amount,
                'reason' => $reason,
                'type' => $type,
                'currency_id' => $client->currency_id,
                'has_whatsapp' => !empty($client->whatsapp_number),
                'whatsapp_number' => $client->whatsapp_number ?? 'NOT SET'
            ]);
            
            event(new AmountReceived(
                $client,
                $amount,
                $reason,
                $client->currency_id
            ));
            
            \Log::info('✅ AmountReceived Event Dispatched Successfully');
        }

    }

    public function addUsed($client, $amount, $item)
    {
        $this->addTransaction(null, $client, null,
            -1 * abs($amount),
            $item, 0,
            'used',
            0);
    }

    public function addUsedTransaction(Request $request, $client, $project, $item)
    {
        if ($item['amount'] == 0) return 0;

        $this->addTransaction($request, $client, $project,
            -1 * abs($item['amount']),
            ($item['reason']), ($item['fee'] ?? 0),
            'used',
            isset($item['is_used']) && $item['is_used'] == '1',
            $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null);

        return 1;
    }

    public function addSend(Request $request, $client, $project, $item)
    {

        if ($item['amount'] == 0) return 0;

        $createdAt = $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null;

        // Create the 'sent' transaction (negative amount)
        $this->addTransaction($request, $client, $project,
            -1 * abs($item['amount']),
            ($item['reason']), $item['fee'],
            'sent',
            isset($item['is_used']) && $item['is_used'] == '1',
            $createdAt);

        // For employees/freelancers: automatically create 'earned' transaction to zero out balance
        // This matches the pattern: received + used = zero, sent + earned = zero
        $isEmployeeOrFreelancer = $client->hasRole('employee') || $client->hasRole('freelancer');
        
        if ($isEmployeeOrFreelancer) {
            $earnedAmount = abs($item['amount']);
            $earnedReason = ($item['reason'] ? $item['reason'] . ' - Earned' : 'Earned from sent payment');
            
            // Create earned transaction to zero out the negative sent transaction
            $this->addTransaction($request, $client, $project,
                $earnedAmount,
                $earnedReason,
                0, // No fee for earned transaction
                'earned',
                false, // Don't add 'used' transaction for earned
                $createdAt
            );
        }

        return 1;
    }

    public function updateProjectDate($project, $first_date, $last_date)
    {
        $this->updateDateEnd($project, $first_date, $last_date);
    }

    public function updateClientDate($client, $first_date, $last_date)
    {
        $this->updateDateEnd($client, $first_date, $last_date);
    }

    public function addRefund(Request $request, $client, $project, $item)
    {
        if ($item['amount'] == 0) return 0;


        $this->addTransaction($request, $client, $project,
            -1 * abs($item['amount']),
            ($item['reason']), ($item['fee']),
            'refunded',
            isset($item['is_used']) && $item['is_used'] == '1',
            $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null);

    }
    public function addEarned(Request $request, $client, $project, $item)
    {
        if ($item['amount'] == 0) return 0;

        $this->addTransaction($request, $client, $project,
            abs($item['amount']),
            ($item['reason']), ($item['fee']),
            'earned',
            isset($item['is_used']) && $item['is_used'] == '1',
            $item['created_at'] ?? $item['transaction_date'] ?? $item['date'] ?? null);

    }

    //

    /**
     * @param $project
     * @param $first_date
     * @param $last_date
     * @return void
     */
    public function updateDateEnd($c_or_p, $first_date, $last_date): void
    {
        if ($c_or_p == null) return;
        $updated = false;
        if (empty($c_or_p->date_start) && !empty($first_date)) {
            $c_or_p->date_start = $first_date;
            $updated = true;
        }
        if (!empty($last_date)) {
            $c_or_p->date_end = $last_date;
            $updated = true;
        }
        if ($updated) {
            $c_or_p->save();
        }
    }

}
