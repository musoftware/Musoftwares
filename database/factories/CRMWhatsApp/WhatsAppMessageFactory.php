<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CRM\Models\WhatsAppMessage;

class WhatsAppMessageFactory extends Factory
{
    protected $model = WhatsAppMessage::class;

    public function definition(): array
    {
        return [
            'uuid'            => (string) Str::uuid(),
            'workspace_id'    => 1,
            'conversation_id' => 1,
            'sender_type'     => $this->faker->randomElement(['agent', 'customer']),
            'type'            => 'text',
            'body'            => $this->faker->paragraph(),
            'delivery_status' => 'delivered',
            'sent_at'         => now(),
            'delivered_at'    => now(),
        ];
    }

    public function fromCustomer(): static { return $this->state(['sender_type' => 'customer']); }
    public function fromAgent(int $agentId = 1): static { return $this->state(['sender_type' => 'agent', 'sender_id' => $agentId]); }
    public function internalNote(): static { return $this->state(['is_internal_note' => true, 'sender_type' => 'agent']); }
    public function failed(): static { return $this->state(['delivery_status' => 'failed', 'failed_reason' => 'Connection timeout']); }
    public function image(): static { return $this->state(['type' => 'image', 'media_url' => $this->faker->imageUrl(), 'media_mime_type' => 'image/jpeg']); }
}
