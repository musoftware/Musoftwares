<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CRM\Models\WhatsAppConversation;

class WhatsAppConversationFactory extends Factory
{
    protected $model = WhatsAppConversation::class;

    public function definition(): array
    {
        return [
            'uuid'                 => (string) Str::uuid(),
            'workspace_id'         => 1,
            'account_id'           => 1,
            'contact_phone'        => $this->faker->e164PhoneNumber(),
            'contact_name'         => $this->faker->name(),
            'type'                 => $this->faker->randomElement(['general', 'lead', 'support', 'sales']),
            'status'               => 'open',
            'priority'             => 'medium',
            'unread_count'         => $this->faker->numberBetween(0, 10),
            'last_message_at'      => now(),
            'last_message_preview' => $this->faker->sentence(),
        ];
    }

    public function open(): static { return $this->state(['status' => 'open']); }
    public function pending(): static { return $this->state(['status' => 'pending']); }
    public function resolved(): static { return $this->state(['status' => 'resolved', 'resolved_at' => now()]); }
    public function urgent(): static { return $this->state(['priority' => 'urgent']); }
    public function assignedTo(int $agentId): static { return $this->state(['assigned_agent_id' => $agentId]); }
    public function forLead(int $leadId): static { return $this->state(['lead_id' => $leadId, 'type' => 'lead']); }
}
