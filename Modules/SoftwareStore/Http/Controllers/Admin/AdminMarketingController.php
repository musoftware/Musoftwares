<?php

namespace Modules\SoftwareStore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SoftwareStore\Services\MarketingKit;

class AdminMarketingController extends Controller
{
    /** One screen with ready-to-post text for every visible product. */
    public function index(MarketingKit $kit): Response
    {
        return Inertia::render('Admin/Marketing/Index', [
            'kits' => $kit->forAll()->values(),
        ]);
    }
}
