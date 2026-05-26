<?php

namespace Database\Factories\CRM;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppCampaignTemplate;

class WhatsAppCampaignTemplateFactory extends Factory
{
    protected $model = WhatsAppCampaignTemplate::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1, 'name' => fake()->words(3, true),
            'type' => 'text', 'body' => 'Hello {{customer_name}}, check out our latest {{company_name}} offer!',
            'placeholders' => [['key' => 'customer_name', 'fallback' => 'Customer'], ['key' => 'company_name', 'fallback' => '']],
            'category' => 'marketing', 'is_approved' => true, 'created_by' => 1,
        ];
    }
}
