<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Workspace;
use App\Models\User;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'type' => 'email',
            'status' => 'draft',
            'created_by' => function () {
                return User::factory()->create()->id;
            },
            'workspace_id' => function () {
                $user = User::factory()->create();
                return Workspace::create([
                    'user_id' => $user->id,
                    'name' => 'Default Workspace',
                ])->id;
            },
        ];
    }
}
