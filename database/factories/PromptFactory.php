<?php

namespace Database\Factories;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prompt>
 */
class PromptFactory extends Factory
{
    protected $model = Prompt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'category' => $this->faker->randomElement(['UI', 'Backend', 'Security', 'Database', 'DevOps', 'Architecture']),
            'prompt' => $this->faker->paragraph(3),
            'description' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'is_featured' => $this->faker->boolean(20),
            'copy_count' => $this->faker->numberBetween(0, 50),
            'tags' => ['ai', 'code'],
        ];
    }
}
