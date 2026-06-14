<?php

namespace Modules\WebTools\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;


class GoldSaverController extends Controller
{
    /**
     * Show the Gold Saver tool page
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('WebTools/Financial/GoldSaver');
    }

    /**
     * Process Gold Saver calculations
     */
    public function process(Request $request): \Inertia\Response
    {
        // This is a simple view-only tool, no processing needed
        return Inertia::render('WebTools/Financial/GoldSaver');
    }
}
