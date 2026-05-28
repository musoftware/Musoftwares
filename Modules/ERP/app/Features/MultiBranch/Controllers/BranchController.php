<?php

namespace Modules\ERP\app\Features\MultiBranch\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\app\Features\MultiBranch\Services\MultiBranchService;
use Modules\ERP\app\Features\MultiBranch\Services\BranchAnalyticsAggregator;
use Modules\ERP\Models\Branch;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;

class BranchController extends Controller
{
    protected MultiBranchService $branchService;
    protected BranchAnalyticsAggregator $analyticsAggregator;

    public function __construct(MultiBranchService $branchService, BranchAnalyticsAggregator $analyticsAggregator)
    {
        $this->branchService = $branchService;
        $this->analyticsAggregator = $analyticsAggregator;
    }

    protected function getTenantAndCheckAccess() {
        $user = Auth::user();
        if (Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
            $ownerUser = $tenant?->user;
        } else {
            $tenant = Tenant::where('user_id', $user?->id)->first();
            $ownerUser = $user;
        }

        if (!$ownerUser || !$ownerUser->hasModuleSubscription('erp-multi-branch')) {
            abort(403, __('erp.unauthorized_multi_branch'));
        }

        return $tenant->id;
    }

    public function dashboard(Request $request)
    {
        $tenantId = $this->getTenantAndCheckAccess();
        
        $activeBranchId = session('active_branch_id') ?: $request->query('branch_id');
        $branches = Branch::where('tenant_id', $tenantId)->get();
        if (!$activeBranchId && $branches->isNotEmpty()) {
            $activeBranchId = $branches->first()->id;
            session(['active_branch_id' => $activeBranchId]);
        }
        
        $metrics = [
            'revenue' => 0, 'transfers_sent' => 0, 'transfers_received' => 0, 'alerts' => 0
        ];
        
        if ($activeBranchId) {
             $rawMetrics = $this->analyticsAggregator->getBranchMetrics($activeBranchId);
             $metrics = array_merge($metrics, $rawMetrics);
        }

        return Inertia::render('ERP/MultiBranch/Dashboard', [
            'activeBranchId' => $activeBranchId ? (int) $activeBranchId : null,
            'branches' => $branches->map(function($b) { return ['id' => $b->id, 'name' => $b->name, 'type' => $b->type]; }),
            'metrics' => $metrics,
        ]);
    }

    public function switchBranch(Request $request)
    {
        $tenantId = $this->getTenantAndCheckAccess();
        
        $request->validate([
            'branch_id' => 'nullable|exists:erp_branches,id'
        ]);

        if ($request->input('branch_id')) {
            $branch = Branch::where('tenant_id', $tenantId)->where('id', $request->input('branch_id'))->firstOrFail();
            session(['active_branch_id' => $branch->id]);
        } else {
            session()->forget('active_branch_id');
        }

        return redirect()->back();
    }

    public function index(Request $request)
    {
        $tenantId = $this->getTenantAndCheckAccess();
        $branches = Branch::where('tenant_id', $tenantId)->get();
        
        if ($request->wantsJson()) {
            return response()->json(['data' => $branches]);
        }

        return Inertia::render('ERP/MultiBranch/Management', [
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->getTenantAndCheckAccess();

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'timezone' => 'nullable|string',
        ]);

        $branch = $this->branchService->createBranch($validated, $tenantId);

        if ($request->wantsJson()) {
            return response()->json(['data' => $branch], 201);
        }
        
        return redirect()->back()->with('success', __('erp.branch_created'));
    }
}
