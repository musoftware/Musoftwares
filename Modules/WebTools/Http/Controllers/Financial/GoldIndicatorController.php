<?php

namespace Modules\WebTools\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;


class GoldIndicatorController extends Controller
{
    /**
     * Show the Gold Indicator tool page
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Financial/GoldIndicator');
    }

    /**
     * Process Gold Indicator calculations
     */
    public function process(Request $request): \Inertia\Response
    {
        // This is a simple view-only tool, no processing needed
        return Inertia::render('WebTools/Financial/GoldIndicator');
    }
}
