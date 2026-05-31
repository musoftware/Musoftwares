<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Lead;
use Modules\CRM\Domains\Leads\Actions\UpdateLeadStatusAction;
use Modules\CRM\Domains\Leads\Actions\DeleteLeadAction;

/**
 * @deprecated Use Domain Actions directly (e.g., UpdateLeadStatusAction, DeleteLeadAction)
 */
class LeadService
{
    public function updateStatus(Lead $lead, string $status): void
    {
        app(UpdateLeadStatusAction::class)->execute($lead, $status);
    }

    public function deleteLead(Lead $lead): void
    {
        app(DeleteLeadAction::class)->execute($lead);
    }
}
