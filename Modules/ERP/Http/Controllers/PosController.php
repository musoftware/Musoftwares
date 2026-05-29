<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PosController extends Controller
{
    private function resolveTenantUser()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return $user;
    }

    private function resolveTenant()
    {
        $user = $this->resolveTenantUser();
        return \Modules\ERP\Models\Tenant::where('user_id', $user->id)->firstOrFail();
    }

    private function checkAddon()
    {
        $user = $this->resolveTenantUser();
        if (!$user || !$user->hasModuleSubscription('erp-pos')) {
            abort(403, __('erp.upgrade_to_enable_pos_system'));
        }
    }

    public function index(Request $request)
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        $search = $request->input('search');

        $productsQuery = \Modules\ERP\Models\Product::where('tenant_id', $tenant->id);
        if ($search) {
            $productsQuery->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $productsQuery->paginate(20)->withQueryString();

        return Inertia::render('ERP/Pos/Index', [
            'products' => $products,
        ]);
    }

    public function checkout(Request $request, \Modules\ERP\Services\PosService $posService)
    {
        $this->checkAddon();
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'client_id' => 'nullable|integer|exists:erp_tenant_clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:erp_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'is_paid' => 'required|boolean',
        ]);

        $invoice = $posService->processCheckout($tenant, $validated);

        return response()->json([
            'message' => __('erp.checkout_successful'),
            'invoice_id' => $invoice->id,
        ]);
    }
}
