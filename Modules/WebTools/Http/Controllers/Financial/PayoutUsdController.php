<?php

namespace Modules\WebTools\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;


class PayoutUsdController extends Controller
{
    /**
     * Show the Payout USD tool page
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Financial/PayoutUsd');
    }

    /**
     * Process Payout USD calculations (Legacy fallback - redirected to index)
     */
    public function process(Request $request): \Inertia\Response
    {
        return $this->index();
    }
}
