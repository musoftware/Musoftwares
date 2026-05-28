<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InventoryController extends Controller
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

    public function index()
    {
        $this->checkAddon();
        $user = $this->resolveTenantUser();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        
        $products = \Modules\ERP\Models\Product::with('currency')
            ->where('tenant_id', $tenant->id)
            ->paginate(15);

        return Inertia::render('ERP/Inventory/Index', [
            'products' => $products,
        ]);
    }
}
