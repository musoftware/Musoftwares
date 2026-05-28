<?php

namespace Modules\ERP\app\Features\MultiBranch\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\app\Features\MultiBranch\Services\MultiBranchService;
use Modules\ERP\Models\Branch;

class BranchController extends Controller
{
    protected MultiBranchService $branchService;

    public function __construct(MultiBranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $branches = Branch::where('tenant_id', $tenantId)->get();
        return response()->json(['data' => $branches]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'timezone' => 'nullable|string',
        ]);

        $tenantId = $request->user()->tenant_id;
        $branch = $this->branchService->createBranch($validated, $tenantId);

        return response()->json(['data' => $branch], 201);
    }
}
