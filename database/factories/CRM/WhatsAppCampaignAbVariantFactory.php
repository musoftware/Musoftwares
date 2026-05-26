<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignAbVariant;

class WhatsAppCampaignAbVariantFactory extends Factory
{
    protected $model = WhatsAppCampaignAbVariant::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1, 'campaign_id' => 1, 'variant_name' => fake()->randomElement(['A', 'B']),
            'message_body' => fake()->sentence(), 'audience_percentage' => 50,
        ];
    }
}
