<?php

namespace Database\Factories;

use App\Models\BlogArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogArticleFactory extends Factory
{
    protected $model = BlogArticle::class;

    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'service_id' => null, // or a service factory if needed
            'language' => $this->faker->randomElement(['en', 'ar']),
            'group_id' => Str::uuid(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->paragraph(),
            'featured_image' => $this->faker->imageUrl(),
            'meta_title' => $title,
            'meta_description' => $this->faker->sentence(),
            'variation_group' => null,
            'cycle_number' => 1,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
