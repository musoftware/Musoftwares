<?php

namespace Modules\CRM\Domains\WorkforceMonitoring\DTOs;

class AgentKpiData
{
    public function __construct(
        public readonly int $agentId,
        public readonly int $callsMade,
        public readonly int $leadsClosed,
        public readonly float $conversionRate,
        public readonly int $tasksCompleted = 0
    ) {
    }
}
