<?php

namespace Modules\CRM\Domains\LeadAcquisition\DTOs;

class LeadImportData
{
    public function __construct(
        public readonly array $data,
        public readonly int $sourceId,
        public readonly ?int $assignedToId = null,
        public readonly ?int $branchId = null
    ) {
    }
}
