<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\WorkforceMonitoring\Actions\CalculateKpisAction;

class WorkspaceController extends Controller
{
    /**
     * Entry point for the CRM. Routes the user to the correct workspace
     * based on their role/addon subscriptions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasModuleSubscription('crm-sales-management')) {
            return redirect()->route('crm.workspaces.manager');
        }

        // Telesales and Collector share the sales-staff addon
        // In a real scenario, this would check a specific role or sub-permission
        // For now, we route to Telesales as default for staff
        if ($user->hasModuleSubscription('crm-sales-staff')) {
            return redirect()->route('crm.workspaces.telesales');
        }

        abort(403, __('errors.unauthorized_workspace'));
    }

    public function collectorWorkspace(Request $request): Response
    {
        // Must have sales-staff addon
        if (!$request->user()->hasModuleSubscription('crm-sales-staff')) {
            abort(403);
        }

        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;

        $totalAdded = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count();

        $recentImports = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('source', 'CSV Import')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('CRM/Workspaces/CollectorDashboard', [
            'stats' => [
                'total_added' => $totalAdded,
                'duplicates_prevented' => 0, // Would be tracked in an import logs table
            ],
            'recentImports' => $recentImports
        ]);
    }

    public function telesalesWorkspace(Request $request): Response
    {
        if (!$request->user()->hasModuleSubscription('crm-sales-staff')) {
            abort(403);
        }

        $userId = $request->user()->id;

        // Fetch leads assigned to this agent grouped by pipeline stage
        $leads = DB::table('leads')
            ->where('assigned_to_id', $userId)
            ->get()
            ->groupBy('pipeline_stage');

        $kpiAction = new CalculateKpisAction();
        $kpis = $kpiAction->execute($userId, now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString());

        return Inertia::render('CRM/Workspaces/TelesalesDashboard', [
            'kpis' => [
                'calls_today' => $kpis->callsMade,
                'pending_followups' => isset($leads['FOLLOW_UP']) ? count($leads['FOLLOW_UP']) : 0,
                'conversion_rate' => $kpis->conversionRate . '%'
            ],
            'pipeline' => $leads
        ]);
    }

    public function managerWorkspace(Request $request): Response
    {
        if (!$request->user()->hasModuleSubscription('crm-sales-management')) {
            abort(403);
        }

        $branchId = $request->user()->branch_id;
        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;

        $activeAgents = DB::table('users')
            ->where('branch_id', $branchId)
            ->count();

        $slaBreaches = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('is_stale', true)
            ->count();

        return Inertia::render('CRM/Workspaces/ManagerDashboard', [
            'branchKpis' => [
                'conversion_rate' => '12.5%', // Mock branch conversion for now
                'active_agents' => $activeAgents
            ],
            'slaAlerts' => [
                'total' => $slaBreaches
            ]
        ]);
    }
}
