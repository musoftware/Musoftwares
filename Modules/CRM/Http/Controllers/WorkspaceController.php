<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
                ->where('crm_workspace_users.workspace_id', $workspaceId)
                ->where('crm_workspace_users.user_id', $user->id)
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

        // Render the Hub so they can choose
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
            ->where('crm_workspace_users.workspace_id', $workspaceId)
            ->where('crm_workspace_users.user_id', $user->id)
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
        $user     = $request->user();

        $overdueInvoices = collect();

        // Pull overdue invoices from ERP only when ERP module is available
        // and the user subscribes to ERP. CRM stays standalone otherwise.
        if (
            $tenantId &&
            $user->hasModuleSubscription('erp') &&
            class_exists(\Modules\ERP\Models\Invoice::class)
        ) {
            try {
                $overdueInvoices = \Modules\ERP\Models\Invoice::where('tenant_id', $tenantId)
                    ->overdue()
                    ->with(['client:id,name,email,phone', 'currency:id,symbol,currency'])
                    ->orderBy('due_date', 'asc')
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::debug('[CRM] ERP module not available for collector workspace: ' . $e->getMessage());
                $overdueInvoices = collect();
            }
        }

        $totalOverdueAmount = 0;
        $agingReport = [
            '0_30'    => 0,
            '31_60'   => 0,
            '61_90'   => 0,
            '90_plus' => 0,
        ];

        $now = now()->startOfDay();
        $clientBalances = [];

        foreach ($overdueInvoices as $invoice) {
            $dueAmount = max(0, $invoice->amount - $invoice->paid_amount);
            $totalOverdueAmount += $dueAmount;
            
            $daysOverdue = $now->diffInDays($invoice->due_date, true);

            if ($daysOverdue <= 30) {
                $agingReport['0_30'] += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $agingReport['31_60'] += $dueAmount;
            } elseif ($daysOverdue <= 90) {
                $agingReport['61_90'] += $dueAmount;
            } else {
                $agingReport['90_plus'] += $dueAmount;
            }

            if (!isset($clientBalances[$invoice->client_id])) {
                $clientBalances[$invoice->client_id] = [
                    'client'         => $invoice->client ?: ['name' => 'Unknown Client', 'email' => '', 'phone' => ''],
                    'total_overdue'  => 0,
                    'invoices_count' => 0,
                    'oldest_due_date' => clone $invoice->due_date,
                    'currency'       => $invoice->currency,
                ];
            }

            $clientBalances[$invoice->client_id]['total_overdue'] += $dueAmount;
            $clientBalances[$invoice->client_id]['invoices_count']++;
            if ($invoice->due_date->isBefore($clientBalances[$invoice->client_id]['oldest_due_date'])) {
                $clientBalances[$invoice->client_id]['oldest_due_date'] = clone $invoice->due_date;
            }
        }

        // Sort clients by highest overdue balance
        usort($clientBalances, function ($a, $b) {
            return $b['total_overdue'] <=> $a['total_overdue'];
        });

        $highRiskAccounts = array_slice($clientBalances, 0, 10);

        return Inertia::render('CRM/Workspaces/CollectorDashboard', [
            'stats' => [
                'total_overdue_amount'   => $totalOverdueAmount,
                'total_overdue_invoices' => count($overdueInvoices),
            ],
            'agingReport'      => $agingReport,
            'highRiskAccounts' => $highRiskAccounts,
            'overdueInvoices'  => $overdueInvoices->take(20)->values(),
        ]);
    }

    public function telesalesWorkspace(Request $request): Response
    {
        $this->ensureAccess($request, ['Sales', 'Agent', 'Manager'], 'crm-sales-staff');

        $isTeam = Auth::guard('crm_team')->check();
        $agentId = $isTeam ? Auth::guard('crm_team')->id() : $request->user()->id;

        $kpiAction = new CalculateKpisAction();
        $kpis = $kpiAction->execute($agentId, now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString());
        
        $activities = DB::table('crm_activities')
            ->where('user_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->event === 'call' ? 'call' : 'stage_change',
                    'agentName' => 'You',
                    'leadName' => json_decode($activity->metadata, true)['lead_name'] ?? 'A lead',
                    'description' => $activity->event,
                    'timeAgo' => \Carbon\Carbon::parse($activity->created_at)->diffForHumans(),
                ];
            });

        return Inertia::render('CRM/Workspaces/TelesalesDashboard', [
            'kpis' => [
                'calls_today' => $kpis->callsMade,
                'pending_followups' => DB::table('leads')->where('assigned_to', $agentId)->where('pipeline_stage', 'FOLLOW_UP')->count(),
                'conversion_rate' => $kpis->conversionRate . '%'
            ],
            'pipeline' => [],
            'activityFeed' => $activities
        ]);
    }

    public function managerWorkspace(Request $request): Response
    {
        $this->ensureAccess($request, ['Manager'], 'crm-sales-management');
        
        $userId = $request->user()->id;

        $workspaceId = session('crm_workspace_id');

        $activeAgents = $workspaceId ? DB::table('crm_team_members')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->count() : 0;

        $staleLeads = $workspaceId ? DB::table('leads')
            ->where('workspace_id', $workspaceId)
            ->where('is_stale', true)
            ->select('id', 'name', 'phone', 'pipeline_stage', 'updated_at', 'assigned_to')
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get() : collect();

        $slaBreaches = $staleLeads->count();

        // Calculate Leaderboard
        $leaderboard = [];
        if ($workspaceId) {
            $agents = DB::table('crm_team_members')
                ->where('crm_team_members.workspace_id', $workspaceId)
                ->where('crm_team_members.status', 'active')
                ->select('crm_team_members.id', 'crm_team_members.name', 'crm_team_members.role')
                ->get();

            $kpiAction = new \Modules\CRM\Domains\WorkforceMonitoring\Actions\CalculateKpisAction();
            $startDate = now()->startOfMonth()->toDateTimeString();
            $endDate = now()->endOfDay()->toDateTimeString();

            foreach ($agents as $agent) {
                $kpis = $kpiAction->execute($agent->id, $startDate, $endDate);
                $leaderboard[] = [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'role' => $agent->role,
                    'calls_made' => $kpis->callsMade,
                    'conversion_rate' => $kpis->conversionRate,
                    'leads_closed' => $kpis->leadsClosed,
                    'tasks_completed' => $kpis->tasksCompleted
                ];
            }

            // Sort by conversions, then calls
            usort($leaderboard, function($a, $b) {
                if ($a['conversion_rate'] === $b['conversion_rate']) {
                    return $b['calls_made'] <=> $a['calls_made'];
                }
                return $b['conversion_rate'] <=> $a['conversion_rate'];
            });

            // Calculate overall branch KPIs
            $branchLeadsClosed = DB::table('leads')
                ->where('workspace_id', $workspaceId)
                ->where('pipeline_stage', 'WON')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            $branchTotalAssigned = DB::table('leads')
                ->where('workspace_id', $workspaceId)
                ->whereBetween('reassigned_at', [$startDate, $endDate])
                ->count();

            $branchConversionRate = $branchTotalAssigned > 0 ? round(($branchLeadsClosed / $branchTotalAssigned) * 100, 2) : 0;

            $branchTasksCompleted = DB::table('crm_activities')
                ->where('workspace_id', $workspaceId)
                ->where('event', 'task_completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
        } else {
            $branchConversionRate = 0;
            $branchTasksCompleted = 0;
        }

        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;
        $user     = $request->user();

        // Pull ERP projects only when ERP module is available and user subscribes.
        // CRM Manager workspace degrades gracefully if ERP is absent.
        $projects = collect();
        if (
            $tenantId &&
            $user->hasModuleSubscription('erp') &&
            class_exists(\Modules\ERP\Models\Project::class)
        ) {
            try {
                $projects = \Modules\ERP\Models\Project::where('tenant_id', $tenantId)
                    ->whereNotIn('status', ['Completed', 'Cancelled'])
                    ->select('id', 'name', 'status', 'due_date', 'budget', 'currency_id')
                    ->with('currency:id,symbol')
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::debug('[CRM] ERP module not available for manager workspace: ' . $e->getMessage());
                $projects = collect();
            }
        }

        $campaigns = \Modules\CRM\Models\Campaign::where('workspace_id', $workspaceId)
            ->whereIn('status', ['ACTIVE', 'scheduled', 'sending'])
            ->select('id', 'name', 'status', 'sent_count', 'total_recipients', 'scheduled_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'progress' => $campaign->total_recipients > 0 ? round(($campaign->sent_count / $campaign->total_recipients) * 100) : 0,
                    'scheduled_at' => $campaign->scheduled_at,
                ];
            });

        return Inertia::render('CRM/Workspaces/ManagerDashboard', [
            'branchKpis' => [
                'conversion_rate' => $branchConversionRate . '%', 
                'active_agents' => $activeAgents,
                'tasks_completed' => $branchTasksCompleted
            ],
            'slaAlerts' => [
                'total' => $slaBreaches,
                'leads' => $staleLeads
            ],
            'leaderboard' => array_slice($leaderboard, 0, 10),
            'projects' => $projects,
            'campaigns' => $campaigns
        ]);
    }

    public function marketingWorkspace(Request $request): Response
    {
        $workspaceId = session('crm_workspace_id');

        $activeCampaigns = \Modules\CRM\Models\Campaign::where('workspace_id', $workspaceId)
            ->where('status', 'ACTIVE')
            ->count();

        $leadsGeneratedToday = DB::table('leads')
            ->where('workspace_id', $workspaceId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $topCampaigns = \Modules\CRM\Models\Campaign::where('workspace_id', $workspaceId)
            ->whereIn('status', ['ACTIVE', 'sending'])
            ->select('id', 'name', 'status', 'sent_count', 'total_recipients')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'progress' => $campaign->total_recipients > 0 ? round(($campaign->sent_count / $campaign->total_recipients) * 100) : 0,
                ];
            });

        return Inertia::render('CRM/Workspaces/MarketingDashboard', [
            'stats' => [
                'active_campaigns' => $activeCampaigns,
                'leads_today'      => $leadsGeneratedToday,
                'cost_per_lead'    => null,
                'roi'              => null,
            ],
            'topCampaigns' => $topCampaigns
        ]);
    }

    public function supportWorkspace(Request $request): Response
    {
        return Inertia::render('CRM/Workspaces/SupportDashboard', [
            'stats' => [
                'open_tickets' => 0,
                'avg_response_time' => '0m',
                'unread_messages' => 0,
                'resolved_today' => 0
            ],
            'priorityMessages' => []
        ]);
    }
}
