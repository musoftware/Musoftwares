<?php

namespace App\Helpers;

use App\Models\Users\AdminSettings;
use App\Models\Finance\CurrenciesExchange;
use App\Models\Finance\Currency;
use App\Models\User;
use App\Services\GameArterService;
use App\Services\GameMonetizeService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class AdsHelper
{


    public static function ShowAds()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user->total_paid > 20000) {
                return false;
            }
            $invoices = $user->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->get();
            $unpaid = 0;
            foreach ($invoices as $invoice) {
                $unpaid += CurrenciesExchange::RateToday($invoice->unpaid_total(), $invoice->currency, $user->currency);
            }
            if ($user->user_balance < $unpaid){
                return true;
            }
        }

        return false;
    }


}
