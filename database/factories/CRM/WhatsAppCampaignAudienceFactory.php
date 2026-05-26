<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignAudience;

class WhatsAppCampaignAudienceFactory extends Factory
{
    protected $model = WhatsAppCampaignAudience::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1, 'name' => fake()->words(2, true) . ' Segment',
            'description' => fake()->sentence(), 'filters' => [['field' => 'status', 'operator' => 'in', 'value' => ['new', 'qualified']]],
            'source_type' => 'leads', 'is_dynamic' => true, 'created_by' => 1,
        ];
    }
}
