<?php

namespace Modules\WebTools\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Inertia\Inertia;


class MultipleCountdownTimerController extends Controller
{
    /**
     * Show the multiple countdown timer tool.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Utilities/MultipleCountdownTimer');
    }
}
