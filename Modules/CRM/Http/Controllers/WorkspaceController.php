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
            // Fallback: If they have no specialized workspaces but reached here,
            // they at least have Core CRM access. Redirect to general dashboard.
            return redirect()->route('crm.dashboard');
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
        
        $activities = DB::table('crm_activities')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->event === 'call' ? 'call' : 'stage_change',
                    'agentName' => 'You', // Since it's the current user's activities
                    'leadName' => json_decode($activity->metadata, true)['lead_name'] ?? 'A lead',
                    'description' => $activity->event,
                    'timeAgo' => \Carbon\Carbon::parse($activity->created_at)->diffForHumans(),
                ];
            });

        return Inertia::render('CRM/Workspaces/TelesalesDashboard', [
            'kpis' => [
                'calls_today' => $kpis->callsMade,
                'pending_followups' => DB::table('leads')->where('assigned_to_id', $userId)->where('pipeline_stage', 'FOLLOW_UP')->count(),
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

        $tenantId = session('tenant_id');
        if (!$tenantId) {
            $tenant = \Modules\ERP\Models\Tenant::where('user_id', $request->user()->id)->first();
            $tenantId = $tenant ? $tenant->id : null;
        }

        $activeAgents = $tenantId ? DB::table('erp_team_members')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count() : 0;

        $staleLeads = $tenantId ? DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('is_stale', true)
            ->select('id', 'name', 'phone', 'pipeline_stage', 'updated_at', 'assigned_to_id')
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get() : collect();

        $slaBreaches = $staleLeads->count();

        // Calculate Leaderboard
        $leaderboard = [];
        if ($tenantId) {
            $agents = DB::table('erp_team_members')
                ->join('users', 'erp_team_members.user_id', '=', 'users.id')
                ->where('erp_team_members.tenant_id', $tenantId)
                ->where('erp_team_members.status', 'active')
                ->select('users.id', 'users.name', 'erp_team_members.role')
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
                    'leads_closed' => $kpis->leadsClosed
                ];
            }

            // Sort by conversions, then calls
            usort($leaderboard, function($a, $b) {
                if ($a['conversion_rate'] === $b['conversion_rate']) {
                    return $b['calls_made'] <=> $a['calls_made'];
                }
                return $b['conversion_rate'] <=> $a['conversion_rate'];
            });
        }

        return Inertia::render('CRM/Workspaces/ManagerDashboard', [
            'branchKpis' => [
                'conversion_rate' => '12.5%', 
                'active_agents' => $activeAgents
            ],
            'slaAlerts' => [
                'total' => $slaBreaches,
                'leads' => $staleLeads
            ],
            'leaderboard' => array_slice($leaderboard, 0, 10)
        ]);
    }

    public function marketingWorkspace(Request $request): Response
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            $tenant = \Modules\ERP\Models\Tenant::where('user_id', $request->user()->id)->first();
            $tenantId = $tenant ? $tenant->id : null;
        }

        $activeCampaigns = DB::table('crm_campaigns')
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();

        $leadsGeneratedToday = DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $topCampaigns = DB::table('crm_campaigns')
            ->where('tenant_id', $tenantId)
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
                'leads_today' => $leadsGeneratedToday,
                'cost_per_lead' => '$2.50',
                'roi' => '125%'
            ],
            'topCampaigns' => $topCampaigns
        ]);
    }

    public function supportWorkspace(Request $request): Response
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            $tenant = \Modules\ERP\Models\Tenant::where('user_id', $request->user()->id)->first();
            $tenantId = $tenant ? $tenant->id : null;
        }

        $openTickets = DB::table('crm_whatsapp_conversations')
            ->where('workspace_id', $tenantId)
            ->where('status', 'open')
            ->count();

        $unreadMessages = DB::table('crm_whatsapp_messages')
            ->where('workspace_id', $tenantId)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->count();
            
        $resolvedToday = DB::table('crm_whatsapp_conversations')
            ->where('workspace_id', $tenantId)
            ->where('status', 'closed')
            ->whereDate('updated_at', today())
            ->count();

        // Calculate average response time (mock algorithm fallback if no data)
        $avgResponseTime = '15m';

        $priorityMessages = DB::table('crm_whatsapp_messages')
            ->where('workspace_id', $tenantId)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->join('crm_whatsapp_conversations', 'crm_whatsapp_messages.conversation_id', '=', 'crm_whatsapp_conversations.id')
            ->select('crm_whatsapp_messages.id', 'crm_whatsapp_messages.body', 'crm_whatsapp_messages.created_at', 'crm_whatsapp_conversations.customer_name', 'crm_whatsapp_conversations.customer_phone')
            ->orderBy('crm_whatsapp_messages.created_at', 'asc') // Oldest unread first
            ->limit(10)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'customer' => $message->customer_name ?? $message->customer_phone,
                    'preview' => \Illuminate\Support\Str::limit($message->body, 50),
                    'timeAgo' => \Carbon\Carbon::parse($message->created_at)->diffForHumans(),
                ];
            });

        return Inertia::render('CRM/Workspaces/SupportDashboard', [
            'stats' => [
                'open_tickets' => $openTickets,
                'avg_response_time' => $avgResponseTime,
                'unread_messages' => $unreadMessages,
                'resolved_today' => $resolvedToday
            ],
            'priorityMessages' => $priorityMessages
        ]);
    }
}
