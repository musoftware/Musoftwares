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
use Modules\ERP\Services\InventoryService;

class ProductController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
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
        $this->authorize('create', Product::class);

        $currencies = \App\Models\Currency::where('status', 'active')->get();

        return Inertia::render('ERP/Inventory/Products/Create', [
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $tenantId = $this->getTenantId();

        $this->inventoryService->createProduct($request->validated(), $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_created_successfully'));
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $tenantId = $this->getTenantId();

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
        $this->authorize('update', $product);

        $tenantId = $this->getTenantId();

        $this->inventoryService->updateProduct($product, $request->validated(), $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_updated_successfully'));
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $this->inventoryService->deleteProduct($product);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_deleted_successfully'));
    }

    public function adjust(Product $product)
    {
        $this->authorize('update', $product);

        return Inertia::render('ERP/Inventory/Products/AdjustStock', [
            'product' => $product,
        ]);
    }

    public function storeAdjustment(AdjustStockRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $tenantId = $this->getTenantId();

        $this->inventoryService->adjustStock($product, (float) $request->change_amount, $request->reason, $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.stock_adjusted_successfully'));
    }
}
