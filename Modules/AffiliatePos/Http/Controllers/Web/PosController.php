<?php

namespace Modules\AffiliatePos\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Modules\AffiliatePos\Models\Product;
use Modules\AffiliatePos\Models\Category;

class PosController extends Controller
{
    public function index(Request $request)
    {
        // Load initial products and categories for the POS to ensure fast first load
        $products = Product::with('skus', 'category', 'images')
            ->where('status', 'active')
            ->paginate(24);
            
        $categories = Category::where('status', 'active')->get();

        return Inertia::render('AffiliatePos/POS/Index', [
            'initialProducts' => $products,
            'categories' => $categories
        ]);
    }
}
