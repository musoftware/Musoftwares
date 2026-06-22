<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SellerPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        return Inertia::render('Marketplace/Seller/Dashboard', [
            'stats' => [
                'total_sales' => 0,
                'active_products' => 0,
                'pending_payouts' => 0,
            ]
        ]);
    }

    public function products(Request $request)
    {
        return Inertia::render('Marketplace/Seller/Products', [
            'products' => []
        ]);
    }

    public function payouts(Request $request)
    {
        return Inertia::render('Marketplace/Seller/Payouts', [
            'payouts' => []
        ]);
    }
}
