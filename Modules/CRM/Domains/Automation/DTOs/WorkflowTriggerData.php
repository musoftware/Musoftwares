<?php

namespace Modules\CRM\Domains\Automation\DTOs;

class WorkflowTriggerData
{
    public function __construct(
        public readonly string $eventName,
        public readonly int $entityId,
        public readonly string $entityType,
        public readonly array $payload
    ) {
    }
}
