<?php

namespace Modules\DigitalProducts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DigitalProducts\Models\DigitalCategory;

class DigitalProductsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'الذكاء الاصطناعي',
                'slug' => 'artificial-intelligence',
                'description' => 'أدلة وكتب تطبيقية في هندسة الأوامر ونماذج الذكاء الاصطناعي والأتمتة.',
                'icon' => 'ri-brain-line',
                'sort_order' => 1,
            ],
            [
                'name' => 'إدارة ونماذج الأعمال',
                'slug' => 'business-models',
                'description' => 'استراتيجيات التوسع، خطط الأعمال الرقمية، وبناء الأنظمة المؤسسية.',
                'icon' => 'ri-briefcase-line',
                'sort_order' => 2,
            ],
            [
                'name' => 'البرمجة وهندسة البرمجيات',
                'slug' => 'programming-tech',
                'description' => 'شروحات عملية في تطوير الويب، التطبيقات، وقواعد البيانات السحابية.',
                'icon' => 'ri-code-s-slash-line',
                'sort_order' => 3,
            ],
            [
                'name' => 'التسويق الرقمي والمبيعات',
                'slug' => 'digital-marketing',
                'description' => 'أدلة إعلانات منصات التواصل، كتابة الإعلانات، وتحسين محركات البحث SEO.',
                'icon' => 'ri-line-chart-line',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $cat) {
            DigitalCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
