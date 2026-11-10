<?php

namespace App\Support;

/**
 * The public site menu, defined once. The Blade header and the React public layout both read it,
 * so every public page shows the same links. The "mega" groups list everything the site offers.
 */
class SiteNav
{
    /** Plain links after the two menus "Our services" and "Our stores". */
    private const MAIN = [
        ['url' => '/portfolio', 'match' => ['portfolio', 'portfolio/*'], 'ar' => 'أعمالنا', 'en' => 'Our work'],
        ['url' => '/estimator', 'match' => ['estimator', 'pricing'], 'ar' => 'احسب التكلفة', 'en' => 'Cost estimator'],
        ['url' => '/about/mahmoud-amin', 'match' => ['about/*'], 'ar' => 'من نحن', 'en' => 'About us'],
    ];

    /** The one answer to "what do you do?", shown at the top of both menus. */
    private const INTRO = [
        'ar' => ['text' => 'إحنا شركة برمجة. بنعمل البرنامج أو الموقع أو التطبيق على مقاس شغلك. البرامج والمتاجر اللي هنا نماذج من شغلنا.', 'cta' => 'ابدأ مشروعك'],
        'en' => ['text' => 'We are a software company. We build the program, website or app that fits your business. The programs and stores here are samples of our work.', 'cta' => 'Start your project'],
    ];

    private const LABELS = [
        'ar' => ['services' => 'خدماتنا', 'stores' => 'متاجرنا', 'stores_note' => 'المتاجر دي إحنا اللي عاملينها وشغالة فعلا. عايز متجر زيها لشغلك؟', 'stores_cta' => 'كلمنا'],
        'en' => ['services' => 'Our services', 'stores' => 'Our stores', 'stores_note' => 'We built these stores and they are live. Want one like them for your business?', 'stores_cta' => 'Talk to us'],
    ];

    public const BRAND = 'Musoftware';

    public const WHATSAPP_URL = 'https://wa.me/201015218548';

    private const FOOTER_COLUMNS = [
        ['ar' => 'المنتجات', 'en' => 'Products', 'links' => [
            ['url' => '/services#programs', 'ar' => 'برامج للكمبيوتر', 'en' => 'Computer programs'],
            ['url' => '/services#systems', 'ar' => 'أنظمة إدارة الأعمال', 'en' => 'Business systems'],
            ['url' => '/how-we-work', 'ar' => 'أنظمة على مقاسك', 'en' => 'Custom systems'],
            ['url' => '/services#services', 'ar' => 'خدمات مواقع وتسويق', 'en' => 'Websites and marketing services'],
            ['url' => '/marketplace/catalog', 'store' => 'marketplace', 'ar' => 'سوق الخدمات', 'en' => 'services marketplace'],
            ['url' => '/store', 'store' => 'shop', 'ar' => 'متجر المنتجات', 'en' => 'online shop'],
            ['url' => '/estimator', 'ar' => 'احسب تكلفة مشروعك', 'en' => 'Estimate your project'],
            ['url' => '/services/tools', 'ar' => 'أدوات مجانية', 'en' => 'Free tools'],
        ]],
        ['ar' => 'موسوفت', 'en' => 'Musoftware', 'links' => [
            ['url' => '/about/mahmoud-amin', 'ar' => 'من نحن', 'en' => 'About us'],
            ['url' => '/portfolio', 'ar' => 'أعمالنا', 'en' => 'Our work'],
            ['url' => '/how-we-work', 'ar' => 'كيف نعمل', 'en' => 'How we work'],
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
            ['url' => '/privacy-policy', 'ar' => 'سياسة الخصوصية وحماية البيانات', 'en' => 'Privacy & Data Protection'],
            ['url' => '/terms-of-service', 'ar' => 'شروط الاستخدام', 'en' => 'Terms of service'],
            ['url' => '/cookie-policy', 'ar' => 'سياسة ملفات الارتباط', 'en' => 'Cookie policy'],
            ['url' => '/privacy-policy#dpo', 'ar' => 'مسؤول حماية البيانات (DPO)', 'en' => 'Data Protection Officer (DPO)'],
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
                ? 'شركة برمجة في السويس، مصر، بيديرها محمود أمين. هنعملك أي برنامج أو موقع أو SaaS أو RaaS، وبنحاول قدر الإمكان إن مشروعك يكبر وينجح.'
                : 'A software company in Suez, Egypt, run by Mahmoud Amin. We build you any program, website, SaaS or RaaS, and do everything we can to help your project grow and succeed.',
            'columns' => array_map(fn (array $column) => [
                'title' => $column[$lang],
                'links' => array_map(fn (array $link) => [
                    'url' => $link['url'],
                    'label' => isset($link['store']) ? SiteStores::name($link['store'], $lang).': '.$link[$lang] : $link[$lang],
                    'external' => str_starts_with($link['url'], 'http'),
                ], $column['links']),
            ], self::FOOTER_COLUMNS),
            'social' => self::SOCIAL,
            'copyright' => '© '.date('Y').' '.self::BRAND.'. '.($isAr ? 'جميع الحقوق محفوظة. صُنع في السويس، مصر.' : 'All rights reserved. Made in Suez, Egypt.'),
            'language_switch' => request()->attributes->getBoolean('locale_locked') ? null : [
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

    /** @return array{all_url: string, labels: array<string, string>, intro: array{text: string, cta: string, url: string}, main: list<array{url: string, label: string, active: bool}>, mega: list<array<string, mixed>>, stores: list<array<string, mixed>>} */
    public static function links(): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $shape = fn (array $link) => [
            'url' => $link['url'],
            'label' => $link[$lang],
            'active' => request()->is(...$link['match']),
        ];

        return [
            'all_url' => '/services',
            'labels' => self::LABELS[$lang] + ['contact_url' => '/start-project'],
            'intro' => self::INTRO[$lang] + ['url' => '/start-project'],
            'main' => array_map($shape, self::MAIN),
            'mega' => SiteMegaMenu::groups($lang),
            'stores' => SiteStores::cards($lang),
        ];
    }
}
