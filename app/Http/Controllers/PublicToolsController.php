<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class PublicToolsController extends Controller
{
    /**
     * Display the GPS coordinates converter tool.
     */
    public function gpsConverter()
    {
        return Inertia::render('Public/Tools/GpsConverter')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.gps_title') . ' | Musoftwares',
                    'description' => __('tools.gps_desc'),
                    'url' => route('public.tools.gps'),
                ],
            ]);
    }

    /**
     * Display the Facebook Page management cost calculator.
     */
    public function facebookCostCalculator()
    {
        return Inertia::render('Public/Tools/FacebookCostCalculator')
            ->withViewData([
                'meta' => [
                    'title' => __('tools.fb_title') . ' | Musoftwares',
                    'description' => __('tools.fb_desc'),
                    'url' => route('public.tools.facebook-cost'),
                ],
            ]);
    }
}
