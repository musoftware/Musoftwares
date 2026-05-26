<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppLabel;

class WhatsAppLabelFactory extends Factory
{
    protected $model = WhatsAppLabel::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1,
            'name'         => $this->faker->unique()->word(),
            'color'        => $this->faker->hexColor(),
            'description'  => $this->faker->sentence(),
            'sort_order'   => $this->faker->numberBetween(0, 10),
        ];
    }
}
