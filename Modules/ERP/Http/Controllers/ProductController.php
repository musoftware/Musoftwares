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
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('create', Product::class);
        $tenantId = $this->getTenantId();

        $currencies = \App\Models\Currency::all();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $hasMultiCurrency = $user && $user->hasModuleSubscription('multi-currency');

        $categories = \Modules\ERP\Models\ProductCategory::where('tenant_id', $tenantId)->get();

        return Inertia::render('ERP/Inventory/Products/Create', [
            'currencies' => $currencies,
            'categories' => $categories,
            'hasMultiCurrency' => $hasMultiCurrency,
            'baseCurrencyId' => $tenant ? $tenant->base_currency_id : ($currencies->first()->id ?? 1),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        Gate::authorize('create', Product::class);

        $tenantId = $this->getTenantId();

        $this->inventoryService->createProduct($request->validated(), $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_created_successfully'));
    }

    public function search(Request $request)
    {
        $search = $request->input('q');
        $tenantId = $this->getTenantId();
        
        $products = Product::with('currency')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'barcode', 'uom', 'tax_rate', 'price', 'currency_id', 'stock_quantity']);
            
        return response()->json($products);
    }

    public function show(Product $product)
    {
        Gate::authorize('view', $product);
        
        $product->load('currency');
        
        $stockLogs = ProductStockLog::with('user:id,name')
            ->where('product_id', $product->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('ERP/Inventory/Products/Show', [
            'product' => $product,
            'stockLogs' => $stockLogs,
        ]);
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        $tenantId = $this->getTenantId();

        $currencies = \App\Models\Currency::all();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $hasMultiCurrency = $user && $user->hasModuleSubscription('multi-currency');

        $categories = \Modules\ERP\Models\ProductCategory::where('tenant_id', $tenantId)->get();

        return Inertia::render('ERP/Inventory/Products/Edit', [
            'product' => $product,
            'currencies' => $currencies,
            'categories' => $categories,
            'hasMultiCurrency' => $hasMultiCurrency,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $tenantId = $this->getTenantId();

        $this->inventoryService->updateProduct($product, $request->validated(), $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_updated_successfully'));
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        $this->inventoryService->deleteProduct($product);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.product_deleted_successfully'));
    }

    public function adjust(Product $product)
    {
        Gate::authorize('update', $product);

        return Inertia::render('ERP/Inventory/Products/AdjustStock', [
            'product' => $product,
        ]);
    }

    public function storeAdjustment(AdjustStockRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $tenantId = $this->getTenantId();

        $this->inventoryService->adjustStock($product, (float) $request->change_amount, $request->reason, $tenantId);

        return redirect()->route('erp.inventory.index')->with('success', __('erp.stock_adjusted_successfully'));
    }
}
