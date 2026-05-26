<?php

namespace Database\Factories\CRMWhatsApp;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\WhatsAppQuickReply;

class WhatsAppQuickReplyFactory extends Factory
{
    protected $model = WhatsAppQuickReply::class;

    public function definition(): array
    {
        return [
            'workspace_id' => 1,
            'shortcut'     => '/' . $this->faker->unique()->word(),
            'title'        => $this->faker->sentence(3),
            'body'         => $this->faker->paragraph(),
            'is_global'    => true,
            'created_by'   => 1,
        ];
    }
}
