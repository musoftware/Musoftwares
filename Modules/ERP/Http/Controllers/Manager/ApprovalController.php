<?php

namespace Modules\ERP\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\LeaveRequest;
use Modules\ERP\Models\WithdrawalRequest;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    /**
     * Display a list of all pending requests for the manager.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $leaveRequests = LeaveRequest::with('member')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->get();

        $withdrawals = WithdrawalRequest::with('client')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->get();

        return Inertia::render('ERP/Manager/Approvals', [
            'leaveRequests' => $leaveRequests,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function showLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $tenantId = $request->user()->tenant_id;
        
        if ($leaveRequest->tenant_id !== $tenantId) {
            abort(403);
        }

        return Inertia::render('ERP/Manager/ReviewLeave', [
            'leaveRequest' => $leaveRequest->load('member.user'),
        ]);
    }

    public function approveLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $managerId = Auth::guard('erp_team')->id() ?? $request->user()->id;

        // EC-MGR-04: Self-Approval Prevention
        if ($leaveRequest->member_id === $managerId) {
            $leaveRequest->update(['status' => 'escalated']);
            return back()->with('error', __('erp.cannot_self_approve_escalated'));
        }

        $leaveRequest->update(['status' => 'approved', 'admin_response' => $request->input('response')]);
        return back()->with('success', __('erp.leave_approved'));
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $leaveRequest->update(['status' => 'rejected', 'admin_response' => $request->input('response')]);
        return back()->with('success', __('erp.leave_rejected'));
    }
}
