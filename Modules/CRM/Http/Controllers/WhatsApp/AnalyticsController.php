<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppAnalyticsService;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected WhatsAppAnalyticsService $analyticsService,
        protected WhatsAppSlaEngine $slaEngine,
        protected CRMWhatsAppLimitsService $limitsService,
    ) {}

    public function overview(Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $workspaceId = session('crm_workspace_id');
        $period = $request->input('period', 'month');

        return response()->json([
            'analytics' => $this->analyticsService->getOverview($workspaceId, $period),
            'usage'     => $this->limitsService->getUsageSummary($workspaceId),
        ]);
    }

    public function agentPerformance(Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $workspaceId = session('crm_workspace_id');
        $period = $request->input('period', 'month');

        return response()->json([
            'agents' => $this->analyticsService->getAgentPerformance($workspaceId, $period),
        ]);
    }

    public function slaCompliance(Request $request)
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $workspaceId = session('crm_workspace_id');
        $period = $request->input('period', 'month');

        return response()->json([
            'sla_stats'   => $this->slaEngine->getComplianceStats($workspaceId, $period),
            'overdue'     => $this->slaEngine->getOverdueConversations($workspaceId),
        ]);
    }
}
