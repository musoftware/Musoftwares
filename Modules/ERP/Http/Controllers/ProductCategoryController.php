<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\ProductCategory;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    private function resolveTenantUser()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return $user;
    }

    private function getTenantId()
    {
        $user = $this->resolveTenantUser();
        return Tenant::where('user_id', $user->id)->value('id');
    }

    public function index()
    {
        $tenantId = $this->getTenantId();
        $categories = ProductCategory::where('tenant_id', $tenantId)->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $tenantId = $this->getTenantId();

        $category = ProductCategory::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => __('erp.category_created_successfully', [], 'en'), // We'll add translations later
            'category' => $category
        ]);
    }

    public function destroy(ProductCategory $category)
    {
        // Add authorization here
        if ($category->tenant_id !== $this->getTenantId()) {
            abort(403);
        }

        $category->delete();

        return response()->json(['message' => __('erp.category_deleted_successfully')]);
    }
}
