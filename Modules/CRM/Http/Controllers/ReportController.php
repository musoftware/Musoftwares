<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function ensureManagerAccess(Request $request)
    {
        $user = $request->user();
        if (!$user->hasModuleSubscription('crm-sales-management')) {
            abort(403, __('errors.feature_not_subscribed'));
        }

        $workspaceId = session('crm_workspace_id');

        $isOwner = DB::table('crm_workspaces')
            ->where('id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) return;

        $workspaceUser = DB::table('crm_workspace_users')
            ->where('crm_workspace_users.workspace_id', $workspaceId)
            ->where('crm_workspace_users.user_id', $user->id)
            ->join('crm_roles', 'crm_workspace_users.role_id', '=', 'crm_roles.id')
            ->select('crm_roles.name')
            ->first();

        $roleName = $workspaceUser ? $workspaceUser->name : 'Unknown';

        // Managers, Admins, and Owners can access Reports
        if (!in_array($roleName, ['Manager']) && $roleName !== 'Admin') {
            abort(403, 'Unauthorized access to department reports.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureManagerAccess($request);

        $workspaceId = session('crm_workspace_id');

        // Simple KPIs for the MVP
        $kpis = [
            'total_leads' => Lead::where('workspace_id', $workspaceId)->count(),
            'total_customers' => Customer::where('workspace_id', $workspaceId)->count(),
            'total_value' => Customer::where('workspace_id', $workspaceId)->sum('total_value'),
            'conversion_rate' => Lead::where('workspace_id', $workspaceId)->count() > 0 ? round((Customer::where('workspace_id', $workspaceId)->count() / Lead::where('workspace_id', $workspaceId)->count()) * 100, 2) : 0,
        ];

        return Inertia::render('CRM/Reports/Index', [
            'kpis' => $kpis,
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureManagerAccess($request);

        $validated = $request->validate([
            'report_type' => 'required|string|in:sales_performance,lead_sources,support_resolutions',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $workspaceId = session('crm_workspace_id');
        $type = $validated['report_type'];
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->endOfDay()->toDateString();

        $filename = "report_{$type}_" . date('Ymd_His') . ".csv";

        return new StreamedResponse(function () use ($type, $workspaceId, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($type === 'sales_performance') {
                fputcsv($handle, ['Agent Name', 'Role', 'Calls Made', 'Leads Closed', 'Tasks Completed']);

                $agents = DB::table('crm_team_members')
                    ->where('workspace_id', $workspaceId)
                    ->where('status', 'active')
                    ->select('id', 'name', 'role')
                    ->get();

                $kpiAction = new \Modules\CRM\Domains\WorkforceMonitoring\Actions\CalculateKpisAction();

                foreach ($agents as $agent) {
                    $kpis = $kpiAction->execute($agent->id, $startDate, $endDate);
                    fputcsv($handle, [
                        $agent->name,
                        $agent->role,
                        $kpis->callsMade,
                        $kpis->leadsClosed,
                        $kpis->tasksCompleted
                    ]);
                }
            } elseif ($type === 'lead_sources') {
                fputcsv($handle, ['Lead Source', 'Total Leads', 'Closed Won', 'Conversion Rate %']);

                $sources = DB::table('leads')
                    ->where('workspace_id', $workspaceId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->select('source', DB::raw('count(*) as total'))
                    ->groupBy('source')
                    ->get();

                foreach ($sources as $source) {
                    $closed = DB::table('leads')
                        ->where('workspace_id', $workspaceId)
                        ->where('source', $source->source)
                        ->where('pipeline_stage', 'WON')
                        ->whereBetween('updated_at', [$startDate, $endDate])
                        ->count();
                    
                    $rate = $source->total > 0 ? round(($closed / $source->total) * 100, 2) : 0;
                    
                    fputcsv($handle, [
                        $source->source ?: 'Unknown',
                        $source->total,
                        $closed,
                        $rate
                    ]);
                }
            } elseif ($type === 'support_resolutions') {
                fputcsv($handle, ['Ticket ID', 'Subject', 'Agent', 'Resolution Time (Hours)', 'Status']);
                
                // Assuming there's a crm_tickets table if SupportDashboard implies it.
                // If not, we can just return an empty CSV with headers.
                if (DB::getSchemaBuilder()->hasTable('crm_tickets')) {
                    $tickets = DB::table('crm_tickets')
                        ->where('workspace_id', $workspaceId)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->get();

                    foreach ($tickets as $ticket) {
                        $resTime = 0;
                        if ($ticket->resolved_at && $ticket->created_at) {
                            $resTime = round((\Carbon\Carbon::parse($ticket->resolved_at)->diffInMinutes(\Carbon\Carbon::parse($ticket->created_at))) / 60, 2);
                        }
                        fputcsv($handle, [
                            $ticket->id,
                            $ticket->subject,
                            $ticket->agent_id, // we could join for agent name
                            $resTime,
                            $ticket->status
                        ]);
                    }
                } else {
                    fputcsv($handle, ['No support tickets table found in MVP', '', '', '', '']);
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
