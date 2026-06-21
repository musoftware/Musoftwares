<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\Lead;

class LeadStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $oldStage;
    public $newStage;

    /**
     * Create a new event instance.
     */
    public function __construct(Lead $lead, string $oldStage, string $newStage)
    {
        $this->lead = $lead;
        $this->oldStage = $oldStage;
        $this->newStage = $newStage;
    }

    /**
     * Convert the event to a payload array for the automation engine.
     */
    public function getAutomationPayload(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_email' => $this->lead->email,
            'lead_name' => $this->lead->name,
            'old_stage' => $this->oldStage,
            'new_stage' => $this->newStage,
            'workspace_id' => $this->lead->workspace_id,
        ];
    }
}
