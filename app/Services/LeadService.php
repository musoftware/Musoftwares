<?php

namespace App\Services;

use Modules\CRM\Models\Lead;

class LeadService
{
    public function updateStatus(Lead $lead, string $status): void
    {
        $lead->status = $status;
        $lead->save();
    }

    public function deleteLead(Lead $lead): void
    {
        $lead->delete();
    }
}
