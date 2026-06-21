<?php

namespace Modules\ERP\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\LeaveRequest;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Export department performance and leave reports as CSV.
     */
    public function export(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $type = $request->query('type', 'team');

        if ($type === 'team') {
            return $this->exportTeam($tenantId);
        }

        if ($type === 'leaves') {
            return $this->exportLeaves($tenantId);
        }

        abort(404, 'Export type not found.');
    }

    private function exportTeam($tenantId)
    {
        $members = TeamMember::where('tenant_id', $tenantId)->get();

        $csvFileName = 'team_report_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Name', 'Email', 'Role', 'Status', 'Joined Date'];

        $callback = function() use($members, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->id,
                    $member->name ?? $member->user?->name,
                    $member->email ?? $member->user?->email,
                    $member->role,
                    $member->status,
                    $member->created_at->format('Y-m-d')
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function exportLeaves($tenantId)
    {
        $leaves = LeaveRequest::with('member')->where('tenant_id', $tenantId)->get();

        $csvFileName = 'leaves_report_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Member Name', 'Type', 'Start Date', 'End Date', 'Status'];

        $callback = function() use($leaves, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($leaves as $leave) {
                fputcsv($file, [
                    $leave->id,
                    $leave->member->user->name ?? 'Unknown',
                    $leave->type,
                    $leave->start_date->format('Y-m-d'),
                    $leave->end_date->format('Y-m-d'),
                    $leave->status
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
