<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppAccount;

class WhatsAppAccountFactory extends Factory
{
    protected $model = WhatsAppAccount::class;

    public function definition(): array
    {
        return [
            'workspace_id'  => 1,
            'name'          => $this->faker->randomElement(['Sales WhatsApp', 'Support WhatsApp', 'Marketing WA']),
            'phone_number'  => $this->faker->e164PhoneNumber(),
            'provider'      => 'baileys',
            'status'        => 'connected',
            'is_default'    => false,
            'last_seen_at'  => now(),
        ];
    }

    public function connected(): static
    {
        return $this->state(['status' => 'connected', 'last_seen_at' => now()]);
    }

    public function disconnected(): static
    {
        return $this->state(['status' => 'disconnected']);
    }
}
