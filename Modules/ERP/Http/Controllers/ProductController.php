<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Product;
use Modules\ERP\Models\ProductStockLog;
use Modules\ERP\Http\Requests\StoreProductRequest;
use Modules\ERP\Http\Requests\UpdateProductRequest;
use Modules\ERP\Http\Requests\AdjustStockRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private function resolveTenantUser()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return $user;
    }

    private function checkAddon()
    {
        $user = $this->resolveTenantUser();
        if (!$user || !$user->hasModuleSubscription('erp-inventory')) {
            abort(403, __('errors.upgrade_to_inventory'));
        }
    }

    private function getTenantId()
    {
        $user = $this->resolveTenantUser();
        return Tenant::where('user_id', $user->id)->value('id');
    }

    public function create()
    {
        $this->checkAddon();
        $currencies = \App\Models\Currency::where('status', 'active')->get();

        return Inertia::render('ERP/Inventory/Products/Create', [
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();

        // Unique SKU check per tenant
        if ($request->sku && Product::where('tenant_id', $tenantId)->where('sku', $request->sku)->exists()) {
            return back()->withErrors(['sku' => __('erp.sku_already_exists')]);
        }

        DB::transaction(function () use ($request, $tenantId) {
            $product = Product::create(array_merge($request->validated(), ['tenant_id' => $tenantId]));

            if ($product->stock_quantity > 0) {
                ProductStockLog::create([
                    'product_id' => $product->id,
                    'tenant_id' => $tenantId,
                    'user_id' => Auth::id(),
                    'change_amount' => $product->stock_quantity,
                    'new_quantity' => $product->stock_quantity,
                    'reason' => __('erp.initial_stock'),
                ]);
            }
        });

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_created_successfully'));
    }

    public function edit(Product $product)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();
        if ($product->tenant_id !== $tenantId) abort(404);

        $currencies = \App\Models\Currency::where('status', 'active')->get();
        $stockLogs = ProductStockLog::with('user:id,name')
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        return Inertia::render('ERP/Inventory/Products/Edit', [
            'product' => $product,
            'currencies' => $currencies,
            'stockLogs' => $stockLogs,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();
        if ($product->tenant_id !== $tenantId) abort(404);

        // Unique SKU check per tenant
        if ($request->sku && Product::where('tenant_id', $tenantId)->where('sku', $request->sku)->where('id', '!=', $product->id)->exists()) {
            return back()->withErrors(['sku' => __('erp.sku_already_exists')]);
        }

        // We do not update stock_quantity through the edit form. It must be done via adjustment.
        $data = $request->validated();
        unset($data['stock_quantity']);

        $product->update($data);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_updated_successfully'));
    }

    public function destroy(Product $product)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();
        if ($product->tenant_id !== $tenantId) abort(404);

        $product->delete();

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_deleted_successfully'));
    }

    public function adjust(Product $product)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();
        if ($product->tenant_id !== $tenantId) abort(404);

        return Inertia::render('ERP/Inventory/Products/AdjustStock', [
            'product' => $product,
        ]);
    }

    public function storeAdjustment(AdjustStockRequest $request, Product $product)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();
        if ($product->tenant_id !== $tenantId) abort(404);

        DB::transaction(function () use ($request, $product, $tenantId) {
            $changeAmount = $request->change_amount;
            $newQuantity = $product->stock_quantity + $changeAmount;

            $product->update(['stock_quantity' => $newQuantity]);

            ProductStockLog::create([
                'product_id' => $product->id,
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'change_amount' => $changeAmount,
                'new_quantity' => $newQuantity,
                'reason' => $request->reason,
            ]);
        });

        return redirect()->route('erp.inventory.index')->with('success', __('erp.stock_adjusted_successfully'));
    }
}
