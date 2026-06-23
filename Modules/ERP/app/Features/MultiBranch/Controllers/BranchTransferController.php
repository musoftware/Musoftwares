<?php

namespace Modules\ERP\app\Features\MultiBranch\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\app\Features\MultiBranch\Services\BranchTransferService;
use Modules\ERP\Models\BranchTransfer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;

class BranchTransferController extends Controller
{
    protected BranchTransferService $transferService;

    public function __construct(BranchTransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    protected function getTenantAndCheckAccess(Request $request) {
        $user = $request->user();
        if (!$user->hasModuleSubscription('erp-multi-branch')) {
            abort(403, __('erp.unauthorized_multi_branch'));
        }
        return $user->tenant->id;
    }

    public function index(Request $request)
    {
        $tenantId = $this->getTenantAndCheckAccess($request);
        $transfers = BranchTransfer::where('tenant_id', $tenantId)->latest()->get();

        $branches = \Modules\ERP\Models\Branch::where('tenant_id', $tenantId)->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $transfers, 'branches' => $branches]);
        }

        return Inertia::render('ERP/MultiBranch/TransferCenter', [
            'transfers' => $transfers,
            'branches' => $branches
        ]);
    }

    public function store(Request $request, $branchId)
    {
        $tenantId = $this->getTenantAndCheckAccess($request);

        $validated = $request->validate([
            'to_branch_id' => 'required|exists:erp_branches,id',
            'type' => 'required|string',
        ]);

        $transfer = $this->transferService->createTransfer(
            $tenantId,
            $branchId,
            $validated['to_branch_id'],
            $validated['type'],
            auth('erp_team')->id()
        );

        if ($request->wantsJson()) {
            return response()->json(['data' => $transfer], 201);
        }
        
        return redirect()->back()->with('success', __('erp.transfer_created'));
    }
}
