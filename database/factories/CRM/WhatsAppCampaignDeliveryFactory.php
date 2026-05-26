<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignDelivery;

class WhatsAppCampaignDeliveryFactory extends Factory
{
    protected $model = WhatsAppCampaignDelivery::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1, 'campaign_id' => 1, 'phone' => fake()->e164PhoneNumber(),
            'contact_name' => fake()->name(), 'rendered_body' => fake()->sentence(),
            'message_type' => 'text', 'status' => 'pending', 'max_retries' => 2,
        ];
    }

    public function sent(): static { return $this->state(['status' => 'sent', 'sent_at' => now(), 'whatsapp_message_id' => 'msg_' . uniqid()]); }
    public function failed(): static { return $this->state(['status' => 'failed', 'failed_reason' => 'Connection timeout']); }
    public function delivered(): static { return $this->state(['status' => 'delivered', 'delivered_at' => now()]); }
}
