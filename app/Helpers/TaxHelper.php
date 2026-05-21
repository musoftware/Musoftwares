<?php

namespace App\Helpers;

use App\Models\Freelance\Client;
use App\Models\Freelance\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaxHelper
{
    /**
     * @var TaxHelper
     */
    protected static $instance = null;

    public static function instance(): ?TaxHelper
    {
        if (self::$instance === null) {
            self::$instance = new TaxHelper();
        }
        return self::$instance;
    }

    public static $old_slices = array(
        '1' => array('from' => 0, 'to' => 8000, 'tax' => 0, 'discount' => 1),
        '2' => array('from' => 8000, 'to' => 30000, 'tax' => 0.1, 'discount' => 0.85),
        '3' => array('from' => 30000, 'to' => 45000, 'tax' => 0.15, 'discount' => 0.45),
        '4' => array('from' => 45000, 'to' => 200000, 'tax' => 0.20, 'discount' => 0.075),
        '5' => array('from' => 200000, 'to' => null, 'tax' => 0.225, 'discount' => 0),
    );

    public static $slices = array(
        '1' => array('from' => 0, 'to' => 15000, 'tax' => 0, 'apply_less_equal' => 600000),
        '2' => array('from' => 15000, 'to' => 30000, 'tax' => 0.025, 'apply_less_equal' => 700000),
        '3' => array('from' => 30000, 'to' => 45000, 'tax' => 0.10, 'apply_less_equal' => 800000),
        '4' => array('from' => 45000, 'to' => 60000, 'tax' => 0.15, 'apply_less_equal' => 900000),
        '5' => array('from' => 60000, 'to' => 200000, 'tax' => 0.20, 'apply_less_equal' => 1000000),
        '6' => array('from' => 200000, 'to' => 400000, 'tax' => 0.225, 'apply_less_equal' => 1000000),
        '7' => array('from' => 400000, 'to' => null, 'tax' => 0.25, 'apply_less_equal' => null),
    );

    public static $slices_company = array(
        '1' => array('from' => 0, 'to' => null, 'tax' => 0.225, 'apply_less_equal' => null),
    );

    public static function calculate_tax_company($income, $accept_discount)
    {
        $annual_income = $income;

        if ($annual_income > 15000) {
            $annual_income -= 15000;
        }
        $income_slices = static::$slices_company;

        if ($income_slices['1']['to'] > $annual_income) return [
            'tax' => 0,
            'slices' => []
        ];

        $total_tax = 0;
        $slice_calcs = array();
        foreach ($income_slices as $income_slice) {
            if ($income_slice['apply_less_equal'] == null || ($income < $income_slice['apply_less_equal'])) {

                if ($income_slice['to'] == null) {
                    $slice = $annual_income;
                } else {
                    $slice = min($annual_income, ($income_slice['to'] - $income_slice['from']));
                }
                $slice_percentage = $income_slice['tax'];
                $slice_tax = round($slice * $slice_percentage);
                $total_tax += $slice_tax;
                $annual_income -= $slice;

                $slice_calcs[] = [
                    'slice' => round($slice),
                    'percentage' => ($slice_percentage * 100) . '%',
                    'tax' => $slice_tax,
                    'net' => round($slice - $slice_tax),
                ];

                if ($annual_income == 0) {
                    break;
                }
            }
        }

        return [
            'tax' => $total_tax,
            'slices' => $slice_calcs
        ];
    }
    public static function calculate_zakat($income)
    {
        return $income * 2.5 / 100;
    }

    public static function calculate_tax($income)
    {
        $annual_income = $income;
        $income_slices = static::$slices;

        if ($income_slices['1']['to'] > $annual_income) return [
            'tax' => 0,
            'slices' => []
        ];

        $total_tax = 0;
        $slice_calcs = array();
        foreach ($income_slices as $income_slice) {
            if ($income_slice['apply_less_equal'] == null || ($income < $income_slice['apply_less_equal'])) {

                if ($income_slice['to'] == null) {
                    $slice = $annual_income;
                } else {
                    $slice = min($annual_income, ($income_slice['to'] - $income_slice['from']));
                }
                $slice_percentage = $income_slice['tax'];
                $slice_tax = round($slice * $slice_percentage);
                $total_tax += $slice_tax;
                $annual_income -= $slice;

                $slice_calcs[] = [
                    'slice' => round($slice),
                    'percentage' => ($slice_percentage * 100) . '%',
                    'tax' => $slice_tax,
                    'net' => round($slice - $slice_tax),
                ];

                if ($annual_income == 0) {
                    break;
                }
            }
        }

        return [
            'tax' => $total_tax,
            'slices' => $slice_calcs
        ];
    }

}
