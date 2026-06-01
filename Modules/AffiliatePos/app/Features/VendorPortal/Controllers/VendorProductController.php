<?php

namespace Modules\AffiliatePos\app\Features\VendorPortal\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\AffiliatePos\Models\Product;
use Modules\AffiliatePos\Models\ProductSku;
use Modules\AffiliatePos\Models\Option;
use Modules\AffiliatePos\Models\OptionValue;
use Illuminate\Support\Str;

class VendorProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('user_id', Auth::id())
            ->with('category', 'tags')
            ->latest()
            ->paginate(20);
            
        return Inertia::render('AffiliatePos/Vendor/Products/Index', [
            'products' => $products
        ]);
    }

    public function create()
    {
        return Inertia::render('AffiliatePos/Vendor/Products/Create');
    }

    public function edit(Product $product)
    {
        if ($product->user_id !== Auth::id()) abort(403);
        
        return Inertia::render('AffiliatePos/Vendor/Products/Edit', [
            'product' => $product->load('skus', 'category')
        ]);
    }

    public function storeSimple(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:affiliate_pos_categories,id',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
        ]);

        return DB::transaction(function () use ($request) {
            $product = Product::create([
                'tenant_id' => $request->header('X-Tenant-ID') ?? 1,
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'name' => $request->name,
                'price' => $request->price,
                'commission' => $request->commission,
                'product_type' => 'simple',
                'status' => 'pending' // Requires admin approval
            ]);

            $sku = ProductSku::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'title' => $product->name,
                'price' => $product->price,
                'status' => 'active'
            ]);

            $sku->increaseStock($request->stock, [
                'description' => 'Initial simple product stock',
                'reference_type' => Product::class,
                'reference_id' => $product->id
            ]);

            return back()->with('success', __('general.simple_product_created_successfully'));
        });
    }

    public function updateStock(Request $request, Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, __('general.unauthorized_access_to_product'));
        }

        $request->validate([
            'sku_id' => 'required|exists:affiliate_pos_product_skus,id',
            'quantity' => 'required|integer' // positive to add, negative to remove
        ]);

        $sku = $product->skus()->findOrFail($request->sku_id);
        
        if ($request->quantity > 0) {
            $sku->increaseStock($request->quantity, ['description' => 'Vendor manual stock increase']);
        } else {
            $sku->decreaseStock(abs($request->quantity), ['description' => 'Vendor manual stock decrease']);
        }

        return back()->with('success', 'Stock updated successfully. Current stock: ' . $sku->stock());
    }

    public function update(Request $request, Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, __('general.unauthorized_access_to_product'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0',
        ]);

        $product->update([
            'name' => $request->name,
            'price' => $request->price,
            'commission' => $request->commission,
        ]);

        return back()->with('success', __('general.product_updated_successfully'));
    }
}
