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
     * Entry point for the CRM Workspaces.
     * Evaluates the user's role in the active workspace and determines which
     * Action Centers (Telesales, Manager, Collector) they have access to.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $workspaceId = session('crm_workspace_id');

        $availableCenters = [];

        // Determine User's Role in this Workspace
        $roleName = null;

        // 1. Is User the Owner of the Workspace?
        $isOwner = DB::table('crm_workspaces')
            ->where('id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) {
            $roleName = 'Owner';
        } else {
            // 2. Fetch from pivot
            $workspaceUser = DB::table('crm_workspace_users')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->join('crm_roles', 'crm_workspace_users.role_id', '=', 'crm_roles.id')
                ->select('crm_roles.name')
                ->first();

            if ($workspaceUser) {
                $roleName = $workspaceUser->name;
            }
        }

        // Feature Constraints
        $hasManagementFeature = $user->hasModuleSubscription('crm-sales-management');
        $hasStaffFeature = $user->hasModuleSubscription('crm-sales-staff');

        // Logic: Grant Access to specific centers based on role
        if ($roleName === 'Owner' || $roleName === 'Admin') {
            if ($hasManagementFeature) $availableCenters[] = 'manager';
            if ($hasStaffFeature) {
                $availableCenters[] = 'telesales';
                $availableCenters[] = 'collector';
            }
        } elseif ($roleName === 'Manager') {
            if ($hasManagementFeature) $availableCenters[] = 'manager';
            if ($hasStaffFeature) $availableCenters[] = 'telesales';
        } elseif ($roleName === 'Sales' || $roleName === 'Agent') {
            if ($hasStaffFeature) $availableCenters[] = 'telesales';
        } elseif ($roleName === 'Viewer') {
            if ($hasStaffFeature) $availableCenters[] = 'collector';
        } else {
            // Default fallback if a role was added but not explicitly matched
            if ($hasStaffFeature) $availableCenters[] = 'telesales';
        }

        if (empty($availableCenters)) {
            abort(403, __('errors.unauthorized_workspace'));
        }

        // If they only have access to exactly 1 center, save them a click and redirect directly
        if (count($availableCenters) === 1) {
            return redirect()->route('crm.workspaces.' . $availableCenters[0]);
        }

        // Otherwise, render the Hub so they can choose
        return Inertia::render('CRM/Workspaces/Index', [
            'availableCenters' => $availableCenters
        ]);
    }

    /**
     * Ensure the current user has access to a specific action center
     */
    private function ensureAccess(Request $request, array $allowedRoles, string $requiredFeature)
    {
        $user = $request->user();
        if (!$user->hasModuleSubscription($requiredFeature)) {
            abort(403, __('errors.feature_not_subscribed'));
        }

        $workspaceId = session('crm_workspace_id');

        $isOwner = DB::table('crm_workspaces')
            ->where('id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) return; // Owner always bypasses role checks

        $workspaceUser = DB::table('crm_workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->join('crm_roles', 'crm_workspace_users.role_id', '=', 'crm_roles.id')
            ->select('crm_roles.name')
            ->first();

        $roleName = $workspaceUser ? $workspaceUser->name : 'Unknown';

        if (!in_array($roleName, $allowedRoles) && $roleName !== 'Admin') {
            abort(403, __('errors.unauthorized_role_access'));
        }
    }

    public function collectorWorkspace(Request $request): Response
    {
        $this->ensureAccess($request, ['Viewer', 'Manager'], 'crm-sales-staff');

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
                'duplicates_prevented' => 0,
            ],
            'recentImports' => $recentImports
        ]);
    }

    public function telesalesWorkspace(Request $request): Response
    {
        $this->ensureAccess($request, ['Sales', 'Agent', 'Manager'], 'crm-sales-staff');

        $userId = $request->user()->id;

        $kpiAction = new CalculateKpisAction();
        $kpis = $kpiAction->execute($userId, now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString());
        
        return Inertia::render('CRM/Workspaces/TelesalesDashboard', [
            'kpis' => [
                'calls_today' => $kpis->callsMade,
                'pending_followups' => DB::table('leads')->where('assigned_to_id', $userId)->where('pipeline_stage', 'FOLLOW_UP')->count(),
                'conversion_rate' => $kpis->conversionRate . '%'
            ],
            'pipeline' => []
        ]);
    }

    public function managerWorkspace(Request $request): Response
    {
        $this->ensureAccess($request, ['Manager'], 'crm-sales-management');

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
                'conversion_rate' => '12.5%', 
                'active_agents' => $activeAgents
            ],
            'slaAlerts' => [
                'total' => $slaBreaches
            ]
        ]);
    }
}
