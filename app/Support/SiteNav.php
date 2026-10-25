<?php

namespace App\Support;

/**
 * The public site menu, defined once. The Blade header and the React public layout both read it,
 * so every public page shows the same links. Less important links live in the "more" group.
 */
class SiteNav
{
    private const MAIN = [
        ['url' => '/services', 'match' => ['services', 'services/*'], 'ar' => 'الخدمات', 'en' => 'Services'],
        ['url' => '/portfolio', 'match' => ['portfolio', 'portfolio/*'], 'ar' => 'أعمالنا', 'en' => 'Our work'],
        ['url' => '/estimator', 'match' => ['estimator', 'pricing'], 'ar' => 'احسب التكلفة', 'en' => 'Cost estimator'],
        ['url' => '/#how-it-works', 'match' => [], 'ar' => 'كيف نعمل', 'en' => 'How we work'],
        ['url' => '/about/mahmoud-amin', 'match' => ['about/*'], 'ar' => 'من نحن', 'en' => 'About us'],
    ];

    private const MORE = [
        ['url' => '/library', 'match' => ['library', 'library/*'], 'ar' => 'المكتبة الرقمية', 'en' => 'Digital library'],
        ['url' => '/platforms', 'match' => ['platforms', 'platforms/*'], 'ar' => 'المنصات', 'en' => 'Platforms'],
        ['url' => '/solutions', 'match' => ['solutions', 'solutions/*'], 'ar' => 'الحلول', 'en' => 'Solutions'],
        ['url' => '/compare/laravel-vs-nodejs', 'match' => ['compare/*'], 'ar' => 'المقارنات والمقالات', 'en' => 'Comparisons and articles'],
    ];

    public const BRAND = 'Musoftware';

    public const WHATSAPP_URL = 'https://wa.me/201015218548';

    private const FOOTER_COLUMNS = [
        ['ar' => 'المنتجات', 'en' => 'Products', 'links' => [
            ['url' => '/services#programs', 'ar' => 'برامج سطح المكتب', 'en' => 'Desktop programs'],
            ['url' => '/services#systems', 'ar' => 'أنظمة إدارة الأعمال', 'en' => 'Business systems'],
            ['url' => '/services#services', 'ar' => 'خدمات مواقع وتسويق', 'en' => 'Websites and marketing services'],
            ['url' => '/marketplace/catalog', 'ar' => 'سوق الخدمات', 'en' => 'Services marketplace'],
            ['url' => '/estimator', 'ar' => 'احسب تكلفة مشروعك', 'en' => 'Estimate your project'],
        ]],
        ['ar' => 'موسوفت', 'en' => 'Musoftware', 'links' => [
            ['url' => '/about/mahmoud-amin', 'ar' => 'من نحن', 'en' => 'About us'],
            ['url' => '/portfolio', 'ar' => 'أعمالنا', 'en' => 'Our work'],
            ['url' => '/#how-it-works', 'ar' => 'كيف نعمل', 'en' => 'How we work'],
            ['url' => '/blog', 'ar' => 'المدونة', 'en' => 'Blog'],
            ['url' => '/compare/laravel-vs-nodejs', 'ar' => 'المقارنات والمقالات', 'en' => 'Comparisons and articles'],
        ]],
        ['ar' => 'تواصل معنا', 'en' => 'Contact', 'links' => [
            ['url' => self::WHATSAPP_URL, 'ar' => 'واتساب مباشر', 'en' => 'WhatsApp direct'],
            ['url' => 'mailto:admin@musoftwares.com', 'ar' => 'admin@musoftwares.com', 'en' => 'admin@musoftwares.com'],
            ['url' => '/company/contact', 'ar' => 'صفحة التواصل', 'en' => 'Contact page'],
            ['url' => '/start-project', 'ar' => 'ابدأ مشروع مخصص', 'en' => 'Start a custom project'],
        ]],
        ['ar' => 'قانوني', 'en' => 'Legal', 'links' => [
            ['url' => '/privacy-policy', 'ar' => 'سياسة الخصوصية', 'en' => 'Privacy policy'],
            ['url' => '/terms-of-service', 'ar' => 'شروط الخدمة', 'en' => 'Terms of service'],
            ['url' => '/cookie-policy', 'ar' => 'سياسة ملفات الارتباط', 'en' => 'Cookie policy'],
        ]],
    ];

    private const SOCIAL = [
        ['url' => 'https://www.linkedin.com/in/musoftwareuno/', 'label' => 'LinkedIn'],
        ['url' => 'https://github.com/musoftware', 'label' => 'GitHub'],
        ['url' => 'https://x.com/MusoftwareUno', 'label' => 'X'],
        ['url' => 'https://www.facebook.com/musoftwares.com.page/', 'label' => 'Facebook'],
    ];

    /**
     * The public footer, defined once for the Blade footer partial and the React SiteFooter.
     *
     * @return array<string, mixed>
     */
    public static function footer(): array
    {
        $isAr = app()->getLocale() === 'ar';
        $lang = $isAr ? 'ar' : 'en';

        return [
            'brand' => self::BRAND,
            'tagline' => $isAr
                ? 'استوديو برمجيات مستقل في السويس، مصر. يديره ويبرمجه محمود أمين. أنظمة وبرامج جاهزة بسعر معلن، وبتستلم الكود.'
                : 'An independent software studio in Suez, Egypt, run and programmed by Mahmoud Amin. Ready-made systems and programs with public prices, and you receive the code.',
            'columns' => array_map(fn (array $column) => [
                'title' => $column[$lang],
                'links' => array_map(fn (array $link) => [
                    'url' => $link['url'],
                    'label' => $link[$lang],
                    'external' => str_starts_with($link['url'], 'http'),
                ], $column['links']),
            ], self::FOOTER_COLUMNS),
            'social' => self::SOCIAL,
            'copyright' => '© '.date('Y').' '.self::BRAND.'. '.($isAr ? 'جميع الحقوق محفوظة. صُنع في السويس، مصر.' : 'All rights reserved. Made in Suez, Egypt.'),
            'language_switch' => [
                'label' => $isAr ? 'English' : 'العربية',
                'url' => request()->fullUrlWithQuery(['lang' => $isAr ? 'en' : 'ar']),
            ],
            'mobile_bar' => [
                'pricing' => ['url' => '/estimator', 'label' => $isAr ? 'الأسعار' : 'Pricing'],
                'whatsapp' => ['url' => self::WHATSAPP_URL.'?text='.rawurlencode($isAr ? 'محمود هنا، أرسل لي تفاصيل مشروعك' : 'Mahmoud here, here are my project details'), 'label' => $isAr ? 'محادثة واتساب' : 'WhatsApp chat'],
                'account' => auth()->check()
                    ? ['url' => '/dashboard', 'label' => $isAr ? 'لوحة التحكم' : 'Dashboard']
                    : ['url' => '/login', 'label' => $isAr ? 'تسجيل الدخول' : 'Sign in'],
            ],
        ];
    }

    /** @return array{main: list<array{url: string, label: string, active: bool}>, more: list<array{url: string, label: string, active: bool}>} */
    public static function links(): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $shape = fn (array $link) => [
            'url' => $link['url'],
            'label' => $link[$lang],
            'active' => $link['match'] !== [] && request()->is(...$link['match']),
        ];

        return ['main' => array_map($shape, self::MAIN), 'more' => array_map($shape, self::MORE)];
    }
}
