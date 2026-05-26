<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppSlaPolicy;

class WhatsAppSlaPolicyFactory extends Factory
{
    protected $model = WhatsAppSlaPolicy::class;

    public function definition(): array
    {
        return [
            'workspace_id'        => 1,
            'name'                => $this->faker->randomElement(['Standard', 'Priority', 'VIP']) . ' SLA',
            'first_response_time' => $this->faker->randomElement([5, 15, 30, 60]),
            'resolution_time'     => $this->faker->randomElement([60, 120, 240, 480]),
            'priority'            => 'medium',
            'business_hours_only' => true,
            'notify_on_breach'    => true,
            'is_default'          => false,
            'is_active'           => true,
        ];
    }

    public function default(): static { return $this->state(['is_default' => true]); }
    public function urgent(): static { return $this->state(['priority' => 'urgent', 'first_response_time' => 5, 'resolution_time' => 60]); }
}
