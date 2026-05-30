<?php

namespace Modules\CRM\Domains\WorkforceMonitoring\Actions;

use Modules\CRM\Domains\WorkforceMonitoring\DTOs\AgentKpiData;

class CalculateAgentKpiAction
{
    public function execute(int $agentId, string $dateRange): AgentKpiData
    {
        // TODO: Implement KPI calculation logic (calls, conversion rate, SLAs)
        return new AgentKpiData($agentId, 0, 0, 0.0);
    }
}
