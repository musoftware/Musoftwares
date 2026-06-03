<?php

namespace App\Helpers;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceHelper
{

    /**
     * @var FinanceHelper
     */
    protected static $instance = null;

    public static function instance(): ?FinanceHelper
    {
        if (self::$instance === null) {
            self::$instance = new FinanceHelper();
        }
        return self::$instance;
    }


    public function format_money($amount, $currency_id): string
    {
        $currencyModel = is_numeric($currency_id) 
            ? Currency::find($currency_id) 
            : Currency::where('currency', $currency_id)->first();

        $formattedAmount = number_format((float)$amount, 2, '.', ',');

        if ($currencyModel) {
            $symbol = $currencyModel->symbol ?? $currencyModel->currency;
            if ($currencyModel->string_format) {
                $fmt = $currencyModel->string_format;
                if (str_contains($fmt, '%')) {
                    $specifiers = ['%01.2f', '%s', '%.2f'];
                    foreach ($specifiers as $spec) {
                        if (str_contains($fmt, $spec)) {
                            return str_replace($spec, $formattedAmount, $fmt);
                        }
                    }
                    try {
                        return sprintf($fmt, $amount);
                    } catch (\Throwable $e) {
                        // ignore and fallback
                    }
                }
                return str_replace(
                    ['{symbol}', '{amount}', '{code}'],
                    [$symbol, $formattedAmount, $currencyModel->currency],
                    $fmt
                );
            }
            return $symbol . $formattedAmount;
        }

        $fallbackCurrency = is_string($currency_id) ? strtoupper($currency_id) : config('app.business_currency', 'USD');
        return $formattedAmount . ' ' . $fallbackCurrency;
    }


    public function format_money_egp($amount): string
    {
        return $this->format_money($amount, 'EGP');
    }

    public static function secondsToTimeHTML($init)
    {
        $isNegative = $init < 0;
        $init = abs($init);
        $day = floor($init / 86400);
        $hours = floor(($init - ($day * 86400)) / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        $sign = $isNegative ? '-' : '';
        return $sign . (!empty($day) ? "<b>$day</b>" . 'D ' : '') . "<b>" . sprintf("%02d", $hours) . "</b>H <b>" . sprintf("%02d", $minutes) . "</b>M <b>" . sprintf("%02d", $seconds) . "</b>S";
    }

    public static function secondsToTime($init)
    {
        $isNegative = $init < 0;
        $init = abs($init);
        $day = floor($init / 86400);
        $hours = floor(($init - ($day * 86400)) / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        $sign = $isNegative ? '-' : '';
        return $sign . (!empty($day) ? "<b>$day</b>" . 'D ' : '') . "<b>" . sprintf("%02d", $hours) . "</b>H <b>" . sprintf("%02d", $minutes) . "</b>M <b>" . sprintf("%02d", $seconds) . "</b>S";
    }

    public function calc_single_finance_summary($item, &$total_paids)
    {

        if (!isset($total_paids[$item->get_currency()])) {
            $total_paids[$item->get_currency()] = 0;
        }
        $total_paids[$item->get_currency()] += round($item->amount, 3);
    }



    public static function SimpleSkipZero($total_paids, $currencies)
    {
        $array = array();

        foreach ($total_paids as $key => $total_paid) {
            if (round($total_paid, 3) == 0) continue;
            $array[] = array(
                'symbol' => $currencies[$key]->symbol,
                'amount' => round($total_paid, 3),
                'amount_str' => self::instance()->format_money(round($total_paid, 3), $currencies[$key]->id),
            );
        }

        if (count($array) == 0) {
            $array[] = array(
                'symbol' => '-',
                'amount' => '-',
                'amount_str' => '-',
            );
        }

        return $array;
    }

    public function advance($user, $client, $selected_year, $selected_month, $timer_type): array
    {
        $invoices = $user->transaction();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }

        $charges_per_day = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->where('type', 'received')
            ->orderBy('day')->get();

        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_refund = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'type',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->Where(function ($query) {
                $query->orWhere('type', 'sent');
                $query->orWhere('type', 'refunded');
            })
            ->orderBy('day')->get();


        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_sent = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->Where(function ($query) {
                $query->orWhere('type', 'used');
            })
            ->orderBy('day')->get();


        $days_range = range(1, date("t", strtotime($selected_year . '-' . $selected_month . '-1')));
        //        $advance = 0;

        $advances = array();
        $used_balance = array();

        foreach ($charges_per_day as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }

            $advances[$invoice->currency] += round($invoice->amounts, 3);
        }

        foreach ($charges_per_day_sent as $invoice) {
            if (!isset($used_balance[$invoice->currency])) {
                $used_balance[$invoice->currency] = 0;
            }
            $used_balance[$invoice->currency] += round($invoice->amounts, 3);
        }
        //
        foreach ($charges_per_day_refund as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }
            $advances[$invoice->currency] -= abs(round($invoice->amounts, 3));
        }


        $invoices_table = array();

        $daily_charges = collect(array_fill_keys($days_range, 0))->map(function ($item, $key) use ($charges_per_day, $charges_per_day_sent, $selected_year, $selected_month, $charges_per_day_refund, &$invoices_table) {

            $array_key = $key . '-' . $selected_month . '-' . $selected_year;

            foreach ($charges_per_day as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => Client::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] +=
                        round($invoice->amounts, 3);

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['advance'], 3);
                }
            }

            foreach ($charges_per_day_refund as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => Client::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] -=
                        abs(round($invoice->amounts, 3));

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['advance'], 3);
                }
            }

            //

            foreach ($charges_per_day_sent as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => Client::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['due'] -=
                        round($invoice->amounts, 3);

                    $invoices_table[$array_key_client][$invoice->currency]['due'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['due'], 3);
                }
            }
        });

        return array(
            $advances,
            $used_balance,
            $invoices_table
        );
    }

    public function advanceNoDates($user, $client, $timer_type): array
    {

        $selected_year = date('Y');
        $selected_month = date('m');


        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }

        $charges_per_day = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->where('type', 'received')
            ->orderBy('day')->get();

        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_refund = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'type',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->Where(function ($query) {
                $query->orWhere('type', 'sent');
                $query->orWhere('type', 'refunded');
            })
            ->orderBy('day')->get();


        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_sent = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'transactions.client_id'
        )
            ->when($timer_type !== 'all', function ($q) use ($timer_type) {
                return $q->where(DB::raw('timer_type'), $timer_type);
            })
            ->Where(function ($query) {
                $query->orWhere('type', 'used');
            })
            ->orderBy('day')->get();


        $days_range = range(1, date("t", strtotime($selected_year . '-' . $selected_month . '-1')));
        //        $advance = 0;

        $advances = array();
        $used_balance = array();

        foreach ($charges_per_day as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }

            $advances[$invoice->currency] += round($invoice->amounts, 3);
        }

        foreach ($charges_per_day_sent as $invoice) {
            if (!isset($used_balance[$invoice->currency])) {
                $used_balance[$invoice->currency] = 0;
            }
            $used_balance[$invoice->currency] += round($invoice->amounts, 3);
        }
        //
        foreach ($charges_per_day_refund as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }
            $advances[$invoice->currency] -= abs(round($invoice->amounts, 3));
        }


        $invoices_table = array();

        $daily_charges = collect(array_fill_keys($days_range, 0))->map(function ($item, $key) use ($charges_per_day, $charges_per_day_sent, $selected_year, $selected_month, $charges_per_day_refund, &$invoices_table) {

            $array_key = $key . '-' . $selected_month . '-' . $selected_year;

            foreach ($charges_per_day as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => User::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] +=
                        round($invoice->amounts, 3);

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['advance'], 3);
                }
            }

            foreach ($charges_per_day_refund as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => Client::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] -=
                        round($invoice->amounts, 3);

                    $invoices_table[$array_key_client][$invoice->currency]['advance'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['advance'], 3);
                }
            }

            //

            foreach ($charges_per_day_sent as $invoice) {
                $array_key_client = $array_key . '-' . $invoice->client_id;
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key_client][$invoice->currency])) {
                        $invoices_table[$array_key_client][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'date' => $array_key,
                            'user' => $invoice->client_id,
                            'client_name' => Client::find($invoice->client_id)->client_name,
                        );
                    }

                    $invoices_table[$array_key_client][$invoice->currency]['due'] -=
                        round($invoice->amounts, 3);

                    $invoices_table[$array_key_client][$invoice->currency]['due'] =
                        round($invoices_table[$array_key_client][$invoice->currency]['due'], 3);
                }
            }
        });

        return array(
            $advances,
            $used_balance,
            $invoices_table
        );
    }


    public static function timer_cluded($user, $client, $selected_year, $selected_month)
    {
        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }

        $charges_per_day = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency'
        )
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->where('type', 'received')
            ->orderBy('day')->get();

        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_refund = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency',
            'type'
        )
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->Where(function ($query) {
                $query->orWhere('type', 'refunded');
            })
            ->orderBy('day')->get();


        $invoices = $user->transactions();
        if ($client !== null) {
            $invoices->where('client_id', $client->id);
        }
        $charges_per_day_sent = $invoices->select(
            DB::raw('MONTH(transactions.created_at) as month'),
            DB::raw('DAY(transactions.created_at) as day'),
            DB::raw('amount as amounts'),
            'transactions.currency'
        )
            ->where(DB::raw('YEAR(transactions.created_at)'), $selected_year)
            ->where(DB::raw('MONTH(transactions.created_at)'), $selected_month)
            ->Where(function ($query) {
                $query->orWhere('type', 'sent');
                $query->orWhere('type', 'used');
            })
            ->orderBy('day')->get();


        $days_range = range(1, date("t", strtotime($selected_year . '-' . $selected_month . '-1')));

        $advances = array();

        foreach ($charges_per_day as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }
            $advances[$invoice->currency] += abs(round($invoice->amounts, 3));
        }

        foreach ($charges_per_day_refund as $invoice) {
            if (!isset($advances[$invoice->currency])) {
                $advances[$invoice->currency] = 0;
            }
            $advances[$invoice->currency] -= abs(round($invoice->amounts, 3));
        }


        $invoices_table = array();

        $daily_charges = collect(array_fill_keys($days_range, 0))->map(function ($item, $key) use ($charges_per_day, $charges_per_day_sent, $selected_year, $selected_month, $charges_per_day_refund, &$invoices_table) {

            $array_key = $key . '-' . $selected_month . '-' . $selected_year;

            foreach ($charges_per_day as $invoice) {
                $invoices_table[$array_key][$invoice->currency] = array(
                    'advance' => 0,
                    'due' => 0,
                    'work_time' => 0,
                    'date' => $array_key,
                );
            }

            foreach ($charges_per_day as $invoice) {
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key][$invoice->currency])) {
                        $invoices_table[$array_key][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'work_time' => 0,
                            'date' => $array_key,
                        );
                    }

                    $invoices_table[$array_key][$invoice->currency]['advance'] +=
                        abs(round($invoice->amounts, 3));
                }
            }

            foreach ($charges_per_day_refund as $invoice) {
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key][$invoice->currency])) {
                        $invoices_table[$array_key][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'work_time' => 0,
                            'date' => $array_key,
                        );
                    }

                    $invoices_table[$array_key][$invoice->currency]['advance'] -=
                        abs(round($invoice->amounts, 3));
                }
            }

            //

            foreach ($charges_per_day_sent as $invoice) {
                if ($invoice->day == $key) {

                    if (!isset($invoices_table[$array_key][$invoice->currency])) {
                        $invoices_table[$array_key][$invoice->currency] = array(
                            'advance' => 0,
                            'due' => 0,
                            'work_time' => 0,
                            'date' => $array_key,
                        );
                    }

                    $invoices_table[$array_key][$invoice->currency]['due'] -=
                        abs(round($invoice->amounts, 3));

                    $invoices_table[$array_key][$invoice->currency]['work_time'] +=
                        $invoice->diff;
                }
            }
        });


        return array(
            $advances,
            $invoices_table
        );
    }

//    public static function SimplifySummaryValuesProject($project, $get_value): array
//    {
//
//
////        $return = [];
////        foreach ($project->ProjectFinanceSummaries()->get() as $finance) {
////            $amount = $get_value($finance);
////            if ($amount == 0) continue;
////
////            $return[] = [
////                'amount_str' => FinanceHelper::instance()->format_money($amount, $finance->currencyRow()->id),
////                'amount' => $amount,
////            ];
////        }
////
////        if ($project->ProjectFinanceSummaries()->count() == 0 || (count($return) == 0)) {
////            return [
////                [
////                    'amount_str' => 0,
////                    'amount' => 0,
////                ]
////            ];
////        }
////        return $return;
//    }

    public function totalWithdrawnThisMonth($user)
    {
        $data = $user->withdraw()
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy('currency')->where('status', 'approved')->select(DB::raw('sum(amount) as amount, currency'))->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateToday($commission->amount, $commission->currency, $user->currency);
            $amount += $user_amount;
        }
        return $amount;
    }

    public function totalWithdrawnLastMonth($user)
    {
        $month = Carbon::now()->subMonth();
        $data = $user->withdraw()
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->groupBy('currency')->where('status', 'approved')->select(DB::raw('sum(amount) as amount, currency'))->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateToday($commission->amount, $commission->currency, $user->currency);
            $amount += $user_amount;
        }
        return $amount;
    }

    private function DrawChange($this_month, $last_month)
    {
        if ($this_month > $last_month) {
            if ($last_month == 0) {
                return '<span class="change up text-success"><em class="icon ni ni-arrow-long-up"></em>100%</span>';
            }
            $percentage = ($this_month * 100 / ($last_month > 0 ? $last_month : 1));
            return '<span class="change up text-success"><em class="icon ni ni-arrow-long-up"></em>' . round($percentage) . '%</span>';
        } else {
//            $percentage = -1 * ($this_month * 100 / ($this_month > 0 ? $this_month : 1));
            $percentage = -1 * ($last_month - $this_month) * 100 / ($last_month > 0 ? $last_month : 1);
            return '<span class="change down text-danger"><em class="icon ni ni-arrow-long-down"></em>' . round($percentage) . '%</span>';
        }
    }

    public function totalWithdrawnThisMonthVSLastMonth($user)
    {
        $this_month = $this->totalWithdrawnThisMonth($user);
        $last_month = $this->totalWithdrawnLastMonth($user);
        return $this->DrawChange($this_month, $last_month);
    }

    /**
     * Round price to a clean display/payment value depending on currency.
     * Used for plan price, invoice timer hour rate, and booking (todo) cost.
     * - EGP: round up to nearest 5 (e.g. 157.99 → 160).
     * - Other (USD etc.): round up by magnitude — 3 digits → step 5, 4 digits → 50, 5+ → 100.
     *
     * @param float $price Raw price
     * @param int|string $currency Currency ID or code (e.g. 2, 'EGP', 'USD')
     * @return float
     */
    public function price_fixer($price, $currency): float
    {
        $code = is_string($currency) ? strtoupper($currency) : null;
        if ($code === null && is_numeric($currency)) {
            $model = Currency::find((int) $currency);
            $code = $model ? strtoupper((string) $model->currency) : 'USD';
        }
        $price = (float) $price;
        if ($price <= 0) {
            return 0.0;
        }

        if ($code === 'EGP') {
            return (float) ((int) ceil($price / 5) * 5);
        }

        $step = 5;
        $magnitude = (int) log10((int) round($price));
        if ($magnitude >= 4) {
            $step = 100;
        } elseif ($magnitude >= 3) {
            $step = 50;
        }
        return (float) ((int) ceil($price / $step) * $step);
    }

    public static function FormatMoneyCurrentUser($money)
    {
        return static::instance()->format_money($money, Auth::user()->currency);
    }

    public static function FormatMoneyCurrentBusiness($money)
    {
        return static::instance()->format_money($money, \App\Models\CurrenciesExchange::BusinessCurrency());
    }

    public function totalSpendThisMonth($user)
    {
        $total_paid = $user->transactions()
            ->whereIn('type', ['received', 'sent', 'refunded'])
            ->groupBy('currency')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->select(DB::raw('sum(amount) as total_amount'), 'currency')->get();

        $total_spend = 0;
        foreach ($total_paid as $item) {
            $total_spend += CurrenciesExchange::RateToday($item->total_amount, $item->currency, $user->currency);
        }
        return $total_spend;
    }


    public function totalSpendThisMonthVSLastMonth($user)
    {
        $this_month = $this->totalSpendThisMonth($user);
        $last_month = $this->totalSpendLastMonth($user);
        return $this->DrawChange($this_month, $last_month);
    }

    public function totalSpendLastMonth($user)
    {
        $month = Carbon::now()->subMonth();
        $total_paid_last_month = $user->transactions()
            ->whereIn('type', ['received', 'sent', 'refunded'])
            ->groupBy('currency')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->select(DB::raw('sum(amount) as total_amount'), 'currency')->get();

        $total_spend_last_month = 0;

        foreach ($total_paid_last_month as $item) {
            $total_spend_last_month += CurrenciesExchange::RateToday($item->total_amount, $item->currency, $user->currency);
        }
        return $total_spend_last_month;
    }

    public static function getPaymentMethodsMessage($invoice, $isArabic = true)
    {
        $unpaid = $invoice->unpaid_total();
        // Convert unpaid amount to EGP for payment link
        $unpaidInEGP = \App\Models\CurrenciesExchange::RateToday($unpaid, $invoice->user->currency, 2);

        $message = "";
        if ($isArabic) {
            $message .= "💳 *طرق الدفع المتاحة:*\n";
        } else {
            $message .= "💳 *Available Payment Methods:*\n";
        }
        $message .= "• InstaPay: 01015218548\n";
        $message .= "• VodafoneCash: 01205850678\n";
        $message .= "• Visa/Credit: https://www.musoftwares.com/payment/instapay?amount={$unpaidInEGP}\n\n";

        return $message;
    }
    private const OVERHEAD_HOURLY_RATE_CACHE_KEY = 'overhead_hourly_rate_daily3';

    /**
     * Clear the cached overhead hourly rate (EGP) used by booking/cost UIs until end of day.
     */
    public static function forgetCachedOverheadHourlyRate(): void
    {
        \Illuminate\Support\Facades\Cache::forget(self::OVERHEAD_HOURLY_RATE_CACHE_KEY);
    }

    public static function calculateOverheadHourlyRate()
    {
        return \Illuminate\Support\Facades\Cache::remember(self::OVERHEAD_HOURLY_RATE_CACHE_KEY, now()->endOfDay(), function () {
            // 1. Calculate Monthly Cost (last 6 months)
            $startDate = \Carbon\Carbon::now()->subMonths(6)->startOfMonth();
            $endDate = \Carbon\Carbon::now()->endOfMonth();

            $costs = \App\Models\CostTransaction::whereBetween('created_at', [$startDate, $endDate])->get();

            $totalCost = 0;
            foreach ($costs as $cost) {
                // Convert to EGP (ID 2).
                $totalCost += \App\Models\CurrenciesExchange::RateToday($cost->amount, $cost->currency, 2);
            }

            $avgMonthlyCost = 0;
            if ($totalCost > 0) {
                $months = 6; // Fixed 6 months window
                $avgMonthlyCost = $totalCost / $months;
            }

            // 2. Daily Cost Rate (Calendar Days)
            $dailyCostBase = $avgMonthlyCost / 22;

            // 3. Safety Margin & Growth Adjustment
            // Default 150% if not set
            $adjustmentPercent = (int)\App\Models\AdminSettings::GetValue('overhead_cost_default', 150);
            if ($adjustmentPercent <= 0) $adjustmentPercent = 150;

            // "Recommended Minimum Daily Charge"
            $recommendedDaily = $dailyCostBase * ($adjustmentPercent / 100);

            // 4. Hourly Rate
            // Logic: The "Daily Charge" must be earned within the 4 working hours (12 PM - 8 PM).
            if ($recommendedDaily > 0) {
                return round($recommendedDaily / 8, 2);
            }

            return 200; // Fallback default
        });
    }

    /**
     * Get the viewer's currency based on user preferences or IP geolocation.
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\Currency
     */
    public function getViewerCurrency(\Illuminate\Http\Request $request): \App\Models\Currency
    {
        // 1. Authenticated User Preference
        if ($user = $request->user()) {
            $currencyId = $user->currency_id;
            if ($currencyId) {
                $currency = \App\Models\Currency::find($currencyId);
                if ($currency) {
                    return $currency;
                }
            }
        }

        // 2. IP Geolocation (Guest or User with no preference)
        $ipService = app(\App\Services\IpGeolocationService::class);
        $currencyCode = $ipService->getCurrencyCodeForIp($request->ip());

        if ($currencyCode) {
            $currency = \App\Models\Currency::where('currency', $currencyCode)->first();
            if ($currency) {
                return $currency;
            }
        }

        // 3. Fallback to System Default (Business Currency or USD)
        $businessCurrencyId = \App\Models\CurrenciesExchange::BusinessCurrency();
        return \App\Models\Currency::find($businessCurrencyId) ?? \App\Models\Currency::where('currency', 'USD')->first();
    }
}
