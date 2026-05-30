<?php

namespace Modules\CRM\Domains\SalesOperations\DTOs;

class LeadStatusUpdateData
{
    public function __construct(
        public readonly int $leadId,
        public readonly string $newStatus,
        public readonly ?string $notes = null,
        public readonly ?int $actionById = null
    ) {
    }
}
