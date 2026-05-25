<?php

namespace Modules\CRM\Observers;

use Modules\CRM\Models\Lead;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        activity()->log('lead.created', $lead, null, $lead->toArray());
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        $dirty = $lead->getDirty();
        $original = array_intersect_key($lead->getOriginal(), $dirty);
        
        // Log generic update
        activity()->log('lead.updated', $lead, $original, $dirty);

        // Track specific important state changes
        if (isset($dirty['status'])) {
            activity()->log('lead.stage_changed', $lead, 
                ['status' => $original['status'] ?? null], 
                ['status' => $dirty['status']]
            );
        }

        if (isset($dirty['assigned_to'])) {
            activity()->log('lead.assigned', $lead, 
                ['assigned_to' => $original['assigned_to'] ?? null], 
                ['assigned_to' => $dirty['assigned_to']]
            );
        }
    }

    /**
     * Handle the Lead "deleted" event.
     */
    public function deleted(Lead $lead): void
    {
        activity()->log('lead.deleted', $lead, $lead->toArray(), null);
    }
}
