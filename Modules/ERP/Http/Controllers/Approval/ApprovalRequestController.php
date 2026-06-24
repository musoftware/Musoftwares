<?php

namespace Modules\ERP\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Approval\ApprovalRequest;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $requests = ApprovalRequest::where('tenant_id', $tenantId)
            ->with(['workflowDefinition', 'requester'])
            ->latest()
            ->get();

        return Inertia::render('ERP/Approval/Requests/Index', [
            'requests' => $requests
        ]);
    }

    public function show(ApprovalRequest $approval_request)
    {
        $approval_request->load(['workflowDefinition.steps', 'requester', 'actions.approver']);
        
        return Inertia::render('ERP/Approval/Requests/Show', [
            'approvalRequest' => $approval_request
        ]);
    }
}
