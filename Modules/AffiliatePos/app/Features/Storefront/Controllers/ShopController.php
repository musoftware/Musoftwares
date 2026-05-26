<?php

namespace Modules\AffiliatePos\app\Features\Storefront\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AffiliatePos\Models\Product;
use Modules\AffiliatePos\Models\Category;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category', 'tags', 'skus')
            ->where('status', 'active');
            
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate(24));
    }

    public function show($id)
    {
        $product = Product::with('category', 'tags', 'skus', 'options.values', 'images')
            ->where('status', 'active')
            ->findOrFail($id);
            
        return response()->json($product);
    }

    public function categories()
    {
        $categories = Category::where('status', 'active')->get();
        return response()->json($categories);
    }
}
