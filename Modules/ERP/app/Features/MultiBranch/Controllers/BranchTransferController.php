<?php

namespace Modules\ERP\app\Features\MultiBranch\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\app\Features\MultiBranch\Services\BranchTransferService;
use Modules\ERP\Models\BranchTransfer;

class BranchTransferController extends Controller
{
    protected BranchTransferService $transferService;

    public function __construct(BranchTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function store(Request $request, $branchId)
    {
        $validated = $request->validate([
            'to_branch_id' => 'required|exists:erp_branches,id',
            'type' => 'required|string',
        ]);

        $transfer = $this->transferService->createTransfer(
            $request->user()->tenant_id,
            $branchId,
            $validated['to_branch_id'],
            $validated['type'],
            $request->user()->id
        );

        return response()->json(['data' => $transfer], 201);
    }
}
