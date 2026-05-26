<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Workspace;
use App\Models\User;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'company' => $this->faker->company,
            'message' => $this->faker->paragraph,
            'status' => 'new',
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
