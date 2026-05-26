<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;

class WhatsAppCampaignSequenceStepFactory extends Factory
{
    protected $model = WhatsAppCampaignSequenceStep::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1, 'sequence_id' => 1, 'step_order' => 1,
            'action_type' => 'send_message', 'message_body' => 'Hello {{customer_name}}!',
            'message_type' => 'text', 'delay_minutes' => 0, 'delay_unit' => 'minutes',
        ];
    }

    public function wait(int $minutes = 60): static { return $this->state(['action_type' => 'wait', 'delay_minutes' => $minutes]); }
    public function condition(): static { return $this->state(['action_type' => 'condition', 'conditions' => [['field' => 'has_replied', 'operator' => 'is_true']]]); }
}
