<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Support\Str;

class WhatsAppCampaignFactory extends Factory
{
    protected $model = WhatsAppCampaign::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(), 'workspace_id' => 1, 'name' => fake()->sentence(3),
            'description' => fake()->paragraph(), 'type' => fake()->randomElement(['broadcast', 'drip', 'nurture', 'reactivation', 'promotional']),
            'status' => 'draft', 'batch_size' => 50, 'batch_delay_seconds' => 5,
            'message_body' => 'Hello {{customer_name}}, ' . fake()->sentence(),
            'message_type' => 'text', 'created_by' => 1,
        ];
    }

    public function running(): static { return $this->state(['status' => 'running', 'started_at' => now()]); }
    public function completed(): static { return $this->state(['status' => 'completed', 'completed_at' => now()]); }
    public function scheduled(): static { return $this->state(['status' => 'scheduled', 'scheduled_at' => now()->addHour()]); }
    public function broadcast(): static { return $this->state(['type' => 'broadcast']); }
    public function drip(): static { return $this->state(['type' => 'drip']); }
}
