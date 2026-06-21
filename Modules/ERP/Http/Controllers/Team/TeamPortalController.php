<?php

namespace Modules\ERP\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\ERP\Models\AttendanceLog;
use Modules\ERP\Models\LeaveRequest as TeamLeaveRequest;
use Modules\ERP\Models\Payslip;

class TeamPortalController extends Controller
{
    /**
     * Display the team member's personal portal (Time Tracking, Leaves, Payroll).
     */
    public function index(Request $request): InertiaResponse
    {
        $member = Auth::guard('erp_team')->user();
        
        if (!$member) {
            abort(403, 'Unauthorized');
        }

        $tenantId = $member->tenant_id;
        $today = now()->toDateString();

        // Time Tracking (Today's log)
        $todayLog = AttendanceLog::where('member_id', $member->id)
            ->where('date', $today)
            ->first();

        // Recent Attendance
        $attendanceHistory = AttendanceLog::where('member_id', $member->id)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Leave Requests
        $leaveRequests = TeamLeaveRequest::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Payslips
        $payslips = Payslip::with(['items', 'currency'])
            ->where('member_id', $member->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return Inertia::render('ERP/Team/MyPortal', [
            'todayLog' => $todayLog,
            'attendanceHistory' => $attendanceHistory,
            'leaveRequests' => $leaveRequests,
            'payslips' => $payslips,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
            ]
        ]);
    }

    public function clockIn(Request $request)
    {
        $member = Auth::guard('erp_team')->user();
        if (!$member) abort(403);

        $today = now()->toDateString();
        $log = AttendanceLog::firstOrCreate(
            ['member_id' => $member->id, 'date' => $today],
            ['tenant_id' => $member->tenant_id, 'clock_in_at' => now()]
        );

        if (!$log->wasRecentlyCreated && !$log->clock_in_at) {
            $log->update(['clock_in_at' => now()]);
        }

        return back()->with('success', __('erp.clocked_in_successfully'));
    }

    public function clockOut(Request $request)
    {
        $member = Auth::guard('erp_team')->user();
        if (!$member) abort(403);

        $today = now()->toDateString();
        $log = AttendanceLog::where('member_id', $member->id)->where('date', $today)->first();

        if (!$log) {
            return back()->with('error', __('erp.no_clock_in_found'));
        }

        if (!$log->clock_out_at) {
            $now = now();
            $minutes = $log->clock_in_at ? $log->clock_in_at->diffInMinutes($now) : 0;
            
            $log->update([
                'clock_out_at' => $now,
                'total_minutes' => $log->total_minutes + $minutes
            ]);
        }

        return back()->with('success', __('erp.clocked_out_successfully'));
    }

    public function requestLeave(Request $request)
    {
        $member = Auth::guard('erp_team')->user();
        if (!$member) abort(403);

        $validated = $request->validate([
            'type' => 'required|string|in:vacation,sick,unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        TeamLeaveRequest::create([
            'tenant_id' => $member->tenant_id,
            'member_id' => $member->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', __('erp.leave_requested_successfully'));
    }
}
