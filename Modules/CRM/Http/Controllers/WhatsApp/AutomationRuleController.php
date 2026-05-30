<?php

namespace Modules\CRM\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Modules\CRM\Http\Requests\WhatsApp\StoreAutomationRuleRequest;
use Modules\CRM\Models\WhatsAppAutomationRule;

class AutomationRuleController extends Controller
{
    public function __construct(protected CRMWhatsAppLimitsService $limitsService) {}

    public function index()
    {
        if (!feature('crm.wa_inbox')) {
            return response()->json(['upgrade_required' => true], 403);
        }

        $rules = WhatsAppAutomationRule::ordered()->get();
        return response()->json(['rules' => $rules]);
    }

    public function store(StoreAutomationRuleRequest $request)
    {
        $workspaceId = session('crm_workspace_id');

        if (!$this->limitsService->canUse($workspaceId, 'max_automation_rules')) {
            return response()->json(['error' => 'Maximum automation rules reached.', 'upgrade_required' => true], 429);
        }

        $rule = WhatsAppAutomationRule::create(array_merge($request->validated(), [
            'workspace_id' => $workspaceId,
        ]));

        return response()->json(['rule' => $rule], 201);
    }

    public function show(WhatsAppAutomationRule $automation)
    {
        return response()->json(['rule' => $automation]);
    }

    public function update(StoreAutomationRuleRequest $request, WhatsAppAutomationRule $automation)
    {
        $automation->update($request->validated());
        return response()->json(['rule' => $automation]);
    }

    public function destroy(WhatsAppAutomationRule $automation)
    {
        $automation->delete();
        return response()->json(['message' => 'Automation rule deleted.']);
    }

    public function toggle(WhatsAppAutomationRule $automation)
    {
        $automation->update(['is_active' => !$automation->is_active]);
        return response()->json(['is_active' => $automation->is_active]);
    }
}
