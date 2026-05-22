<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Finance\Currency;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ColorHelper
{

    public static function generateColorFromHash($string)
    {
        // Compute the MD5 hash of the string
        $hash = md5($string);

        // Extract a part of the hash to use as a color
        // We'll take the first 6 characters of the hash to get a valid HEX color
        $color = substr($hash, -6);

        // Return the color
        return '#' . $color . '77';
    }


}
