<?php

namespace App\Helpers;

use App\Models\Currency;

class InvoiceHelper
{
    /**
     * @var InvoiceHelper
     */
    protected static $instance = null;

    public static function instance(): ?InvoiceHelper
    {
        if (self::$instance === null) {
            self::$instance = new InvoiceHelper;
        }

        return self::$instance;
    }

    public static function SimplifySummaryValuesProject($project, $get_value): array
    {
        $return = [];
        $amount = $get_value($project);

        $return[] = [
            'symbol' => $project->currencyRow()->symbol,
            'amount' => $amount,
            'amount_str' => FinanceHelper::instance()->format_money($amount, $project->currencyRow()->id),
        ];

        //        foreach ($project->ProjectFinanceSummaries()->get() as $finance) {
        //            $amount = $get_value($finance);
        //            if ($amount == 0) continue;
        //            $return[] = [
        //                'symbol' => $finance->currencyRow()->symbol,
        //                'amount' => $amount,
        //            ];
        //        }
        //        if ($project->ProjectFinanceSummaries()->count() == 0 || (count($return) == 0)) {
        //            return [
        //                [
        //                    'symbol' => $project->currencyRow()->symbol,
        //                    'amount' => 0,
        //                ]
        //            ];
        //        }
        return $return;
    }

    public static function GroupCurrency($advances, $used_balance): array
    {
        $return = [];
        foreach (Currency::as_array() as $finance) {
            $amount = (isset($advances[$finance->id])) ? round($advances[$finance->id], 3) : 0;
            $amount += (isset($used_balance[$finance->id])) ? round($used_balance[$finance->id], 3) : 0;

            if ($amount == 0) {
                continue;
            }
            $return[] = [
                'symbol' => $finance->symbol,
                'id' => $finance->id,
                'currency' => $finance->currency,
            ];
        }

        return $return;
    }

    public static function SimplifyTotalSummaryValues($get_value): array
    {
        $return = [];
        foreach (Currency::as_array() as $finance) {
            $f = new \stdClass;
            $f->currency = $finance->id;
            $amount = $get_value($f);

            if ($amount == 0) {
                continue;
            }
            $return[] = [
                'symbol' => $finance->symbol,
                'amount' => $amount,
            ];
        }

        return $return;
    }

    public static function skipZeroAmount($items, $defaultSymbol)
    {
        $new_items = [];
        foreach ($items as $key => $advance) {
            if (round($advance['amount'], 3) != 0) {

                $currency = $advance['currency'] ?? $key;
                $new_items[$key] = $advance;
                if (! isset($new_items[$key]['amount_str'])) {
                    $new_items[$key]['amount_str'] = FinanceHelper::instance()->format_money($new_items[$key]['amount'], $currency);

                }
            }
        }
        if (count($new_items) > 0) {
            return $new_items;
        } else {
            return [
                [
                    'amount' => '0',
                    'amount_str' => '-',
                    'symbol' => '-',
                ],
            ];
        }
    }

    public static function process_own_invoices_table($non_timer_invoices_table, $currencies_as_array): array
    {

        $currencies = $currencies_as_array;
        $data_keys_date = [];
        $index = 0;

        foreach ($currencies_as_array as $key => $cur) {
            $balance[$key] = 0;
        }

        foreach ($non_timer_invoices_table as $invoice_table) {
            $index = ++$index;
            foreach ($currencies_as_array as $key => $cur) {

                if (! isset($invoice_table[$key])) {
                    continue;
                }

                if (! isset($data_keys_date[$invoice_table[$key]['date']])) {
                    $data_keys_date[$invoice_table[$key]['date']] = [];
                }

                $transaction = [];

                if (isset($data_keys_date[$invoice_table[$key]['date']][$invoice_table[$key]['user']])) {
                    $transaction = $data_keys_date[$invoice_table[$key]['date']][$invoice_table[$key]['user']];
                }

                $transaction['index'] = $index;
                $transaction['date'] = $invoice_table[$key]['date'];

                //                if (isset($invoice_table[$key]['client_name'])) {
                $transaction['client_name'] = $invoice_table[$key]['client_name'];
                $transaction['user'] = $invoice_table[$key]['user'];
                //                }

                if (! isset($transaction['advance'])) {
                    $transaction['advance'] = [];
                }
                $transaction['advance'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'currency' => $key, 'amount' => $invoice_table[$key]['advance']];

                if (! isset($transaction['due'])) {
                    $transaction['due'] = [];
                }
                $transaction['due'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'currency' => $key, 'amount' => $invoice_table[$key]['due']];

                if (! isset($transaction['total'])) {
                    $transaction['total'] = [];
                }

                $transaction['total'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'currency' => $key, 'amount' => $invoice_table[$key]['advance'] + $invoice_table[$key]['due'],
                    ];

                if (! isset($balance[$key])) {
                    $balance[$key] = 0;
                }
                $balance[$key] += $invoice_table[$key]['advance'] + $invoice_table[$key]['due'];

                $transaction['balance'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'currency' => $key, 'amount' => $balance[$key],
                    ];

                $data_keys_date[$invoice_table[$key]['date']][$invoice_table[$key]['user']] = $transaction;

            }

        }

        return array_values($data_keys_date);
    }

    public static function process_invoices_table($non_timer_invoices_table, $currencies_as_array): array
    {

        $currencies = $currencies_as_array;
        $data_keys_date = [];
        $index = 0;

        foreach ($currencies_as_array as $key => $cur) {
            $balance[$key] = 0;
        }

        foreach ($non_timer_invoices_table as $invoice_table) {
            $index = ++$index;
            foreach ($currencies_as_array as $key => $cur) {

                if (! isset($invoice_table[$key])) {
                    continue;
                }

                if (! isset($data_keys_date[$invoice_table[$key]['date']])) {
                    $data_keys_date[$invoice_table[$key]['date']] = [];
                }

                $data_keys_date[$invoice_table[$key]['date']]['index'] = $index;
                $data_keys_date[$invoice_table[$key]['date']]['date'] = $invoice_table[$key]['date'];
                $data_keys_date[$invoice_table[$key]['date']]['work_time'] = FinanceHelper::secondsToTimeHTML($invoice_table[$key]['work_time']);

                if (isset($invoice_table[$key]['client_name'])) {
                    $data_keys_date[$invoice_table[$key]['date']]['client_name'] = $invoice_table[$key]['client_name'];
                }

                if (! isset($data_keys_date[$invoice_table[$key]['date']]['advance'])) {
                    $data_keys_date[$invoice_table[$key]['date']]['advance'] = [];
                }
                $data_keys_date[$invoice_table[$key]['date']]['advance'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'amount' => $invoice_table[$key]['advance']];

                if (! isset($data_keys_date[$invoice_table[$key]['date']]['due'])) {
                    $data_keys_date[$invoice_table[$key]['date']]['due'] = [];
                }
                $data_keys_date[$invoice_table[$key]['date']]['due'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'amount' => $invoice_table[$key]['due']];

                if (! isset($data_keys_date[$invoice_table[$key]['date']]['total'])) {
                    $data_keys_date[$invoice_table[$key]['date']]['total'] = [];
                }

                $data_keys_date[$invoice_table[$key]['date']]['total'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'amount' => $invoice_table[$key]['advance'] + $invoice_table[$key]['due'],
                    ];

                if (! isset($balance[$key])) {
                    $balance[$key] = 0;
                }
                $balance[$key] += $invoice_table[$key]['advance'] + $invoice_table[$key]['due'];

                $data_keys_date[$invoice_table[$key]['date']]['balance'][] =
                    ['symbol' => $currencies[$key]['symbol'], 'amount' => $balance[$key],
                    ];

            }

        }

        return array_values($data_keys_date);
    }
}
