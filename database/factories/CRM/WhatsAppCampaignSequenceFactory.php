<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignSequence;

class WhatsAppCampaignSequenceFactory extends Factory
{
    protected $model = WhatsAppCampaignSequence::class;

    public function definition(): array
    {
        return ['workspace_id' => 1, 'campaign_id' => 1, 'name' => fake()->words(2, true) . ' Sequence', 'is_active' => true, 'total_steps' => 3];
    }
}
