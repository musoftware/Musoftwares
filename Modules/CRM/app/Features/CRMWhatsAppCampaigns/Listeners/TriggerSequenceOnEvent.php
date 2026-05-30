<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignSequenceTriggered;

class TriggerSequenceOnEvent
{
    public function handle($event): void
    {
        if (!($event instanceof WhatsAppCampaignSequenceTriggered)) {
            return;
        }

        // Log the sequence trigger
        try {
            \Modules\CRM\Models\WhatsAppCampaignEvent::record(
                $event->campaign,
                'sequence_triggered',
                "Sequence '{$event->sequence->name}' triggered",
                null,
                [
                    'sequence_id'   => $event->sequence->id,
                    'sequence_name' => $event->sequence->name,
                    'total_steps'   => $event->sequence->total_steps,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning("Sequence trigger logging failed: {$e->getMessage()}");
        }
    }
}
