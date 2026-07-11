<?php

namespace App\Helpers;

use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Auth;

class AdsHelper
{
    public static function ShowAds()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->total_paid > 20000) {
                return false;
            }
            $invoices = $user->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->get();
            $unpaid = 0;
            foreach ($invoices as $invoice) {
                $unpaid += CurrenciesExchange::RateToday($invoice->unpaid_total(), $invoice->currency, $user->currency);
            }
            if ($user->user_balance < $unpaid) {
                return true;
            }
        }

        return false;
    }
}
