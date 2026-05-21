<?php

namespace App\Helpers;

use App\Models\Users\AdminSettings;
use App\Models\Finance\Currency;
use App\Models\User;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Meneses\LaravelMpdf\LaravelMpdfWrapper;
use PDF;

class PlanHelper
{

    public static function buttonText()
    {
        if (empty(\auth()->user()->subscription_plan)) {
            return 'Choose Plan';
        }else{
            return 'Upgrade';
        }
    }


}
