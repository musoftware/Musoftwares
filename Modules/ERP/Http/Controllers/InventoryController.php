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
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
        }
        return $user;
    }



    public function index(Request $request)
    {
        $user = $this->resolveTenantUser();
        $tenant = auth('erp_team')->user()->tenant;
        
        $query = \Modules\ERP\Models\Product::with('currency')
            ->where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(15)->withQueryString();

        $hasInventoryFeature = $user && $user->hasModuleSubscription('erp-inventory');

        return Inertia::render('ERP/Inventory/Index', [
            'products' => $products,
            'filters' => $request->only('search'),
            'hasInventoryFeature' => $hasInventoryFeature,
        ]);
    }
}
