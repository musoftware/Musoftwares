<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'description' => 'Custom websites, web applications, and bug fixes.',
            ],
            [
                'name' => 'Graphic Design',
                'description' => 'Logos, branding, illustrations, and UI/UX design.',
            ],
            [
                'name' => 'Digital Marketing',
                'description' => 'SEO, social media marketing, and email campaigns.',
            ],
            [
                'name' => 'Writing & Translation',
                'description' => 'Articles, blog posts, copywriting, and translations.',
            ],
            [
                'name' => 'Video & Animation',
                'description' => 'Video editing, animation, and motion graphics.',
            ],
            [
                'name' => 'Music & Audio',
                'description' => 'Voice overs, mixing, mastering, and sound design.',
            ],
            [
                'name' => 'Programming & Tech',
                'description' => 'Software development, cybersecurity, and data analysis.',
            ],
            [
                'name' => 'Business',
                'description' => 'Virtual assistance, market research, and business consulting.',
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                ]
            );
        }
    }
}
