<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppAutomationRule;

class WhatsAppAutomationRuleFactory extends Factory
{
    protected $model = WhatsAppAutomationRule::class;

    public function definition(): array
    {
        return [
            'workspace_id'  => 1,
            'name'          => $this->faker->sentence(3),
            'type'          => 'auto_reply',
            'trigger_event' => 'message.received',
            'conditions'    => [['field' => 'is_first_message', 'operator' => 'is_true', 'value' => true]],
            'actions'       => [['type' => 'send_reply', 'message' => 'Thank you for contacting us!']],
            'is_active'     => true,
            'priority'      => 0,
        ];
    }

    public function inactive(): static { return $this->state(['is_active' => false]); }
    public function awayMessage(): static { return $this->state(['type' => 'away_message']); }
}
