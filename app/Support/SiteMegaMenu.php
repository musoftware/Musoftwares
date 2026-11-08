<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;

/**
 * Everything the site offers, for the "All services" menu. Products and free tools are read from
 * their real source, so a new one shows here without touching this file.
 */
class SiteMegaMenu
{
    private const PRODUCTS_CACHE_KEY = 'site_nav_products';

    private const PRODUCTS_CACHE_SECONDS = 600;

    private const STORE_GROUPS = [
        'programs' => ['url' => '/services#programs', 'ar' => 'برامج جاهزة', 'en' => 'Ready programs'],
        'systems' => ['url' => '/services#systems', 'ar' => 'أنظمة إدارة الأعمال', 'en' => 'Business systems'],
        'services' => ['url' => '/services#services', 'ar' => 'مواقع وتسويق', 'en' => 'Websites and marketing'],
    ];

    private const MARKETPLACE = ['url' => '/marketplace/catalog', 'ar' => 'سوق الخدمات', 'en' => 'Services marketplace', 'links' => [
        ['url' => '/marketplace/catalog', 'ar' => 'تصفح كل الخدمات', 'en' => 'Browse all services'],
        ['url' => '/marketplace/services/create', 'ar' => 'اعرض خدمتك للبيع', 'en' => 'Sell your service'],
        ['url' => '/marketplace/orders', 'ar' => 'طلباتي', 'en' => 'My orders'],
        ['url' => '/marketplace/favorites', 'ar' => 'المفضلة', 'en' => 'Favorites'],
    ]];

    /** Groups that come after the products and tools. */
    private const STATIC_GROUPS = [
        ['ar' => 'ابدأ مشروعك', 'en' => 'Start a project', 'links' => [
            ['url' => '/start-project', 'ar' => 'كوّن نظامك خطوة بخطوة', 'en' => 'Build your system step by step'],
            ['url' => '/estimator', 'ar' => 'احسب التكلفة', 'en' => 'Cost estimator'],
            ['url' => '/white-label', 'ar' => 'بيع برامجنا باسمك', 'en' => 'Sell our programs under your name'],
        ]],
        ['ar' => 'تعلّم واقرأ', 'en' => 'Learn', 'links' => [
            ['url' => '/blog', 'ar' => 'المدونة', 'en' => 'Blog'],
            ['url' => '/library', 'ar' => 'المكتبة الرقمية', 'en' => 'Digital library'],
            ['url' => '/compare/laravel-vs-nodejs', 'ar' => 'المقارنات والمقالات', 'en' => 'Comparisons and articles'],
            ['url' => '/listing', 'ar' => 'لوحة الوظائف', 'en' => 'Job board'],
        ]],
        ['ar' => 'موسوفت', 'en' => 'Musoftware', 'links' => [
            ['url' => '/about/mahmoud-amin', 'ar' => 'من نحن', 'en' => 'About us'],
            ['url' => '/portfolio', 'ar' => 'أعمالنا', 'en' => 'Our work'],
            ['url' => '/how-we-work', 'ar' => 'كيف نعمل', 'en' => 'How we work'],
            ['url' => '/company/careers', 'ar' => 'وظائف في موسوفت', 'en' => 'Careers at Musoftware'],
            ['url' => '/company/contact', 'ar' => 'تواصل معنا', 'en' => 'Contact us'],
        ]],
        ['ar' => 'من داخل حسابك', 'en' => 'In your account', 'links' => [
            ['url' => '/sms-payment-gateway', 'ar' => 'بوابة الدفع', 'en' => 'Payment gateway'],
            ['url' => '/whatsapp-sender', 'ar' => 'إرسال واتساب', 'en' => 'WhatsApp sender'],
            ['url' => '/micro-services', 'ar' => 'الخدمات المصغرة', 'en' => 'Micro services'],
            ['url' => '/my-licenses', 'ar' => 'تراخيص برامجي', 'en' => 'My licenses'],
            ['url' => '/subscriptions/plans', 'ar' => 'خطط الاشتراك', 'en' => 'Subscription plans'],
            ['url' => '/referrals', 'ar' => 'اكسب من الإحالات', 'en' => 'Earn from referrals'],
            ['url' => '/tickets', 'ar' => 'الدعم الفني', 'en' => 'Support tickets'],
        ]],
    ];

    /** @return list<array{title: string, url: ?string, active: bool, links: list<array{url: string, label: string, active: bool}>}> */
    public static function groups(string $lang): array
    {
        $products = collect(self::products())->groupBy('group');

        $storeGroups = array_map(fn (string $key) => self::shapeGroup([
            ...self::STORE_GROUPS[$key],
            'links' => $products->get($key, collect())->map(fn (array $product) => [
                'url' => '/services/'.$product['slug'],
                'ar' => $product['ar'],
                'en' => $product['en'],
            ])->all(),
        ], $lang), array_keys(self::STORE_GROUPS));

        return [
            self::shapeGroup(self::MARKETPLACE, $lang),
            ...array_values(array_filter($storeGroups, fn (array $group) => $group['links'] !== [])),
            self::shapeGroup(['url' => '/services/tools', 'ar' => 'أدوات مجانية', 'en' => 'Free tools', 'links' => self::toolLinks()], $lang),
            ...array_map(fn (array $group) => self::shapeGroup($group, $lang), self::STATIC_GROUPS),
        ];
    }

    /** @return list<array{url: string, ar: string, en: string}> */
    private static function toolLinks(): array
    {
        return array_map(fn (string $slug) => [
            'url' => '/services/tools/'.$slug,
            'ar' => __("free_tools.tools.{$slug}.name", [], 'ar'),
            'en' => __("free_tools.tools.{$slug}.name", [], 'en'),
        ], FreeTools::slugs());
    }

    /**
     * Plain arrays so the cache never holds models. This runs on every public page, hence the cache.
     *
     * @return list<array{slug: string, group: string, ar: string, en: string}>
     */
    private static function products(): array
    {
        return Cache::remember(self::PRODUCTS_CACHE_KEY, self::PRODUCTS_CACHE_SECONDS, fn () => SoftwareProduct::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SoftwareProduct $product) => [
                'slug' => $product->slug,
                'group' => $product->group(),
                'ar' => $product->localized('name', 'ar'),
                'en' => $product->localized('name', 'en'),
            ])
            ->all());
    }

    /** @param array{url?: string, ar: string, en: string, links: array<int, array{url: string, ar: string, en: string}>} $group */
    private static function shapeGroup(array $group, string $lang): array
    {
        $links = array_map(fn (array $link) => [
            'url' => $link['url'],
            'label' => $link[$lang],
            'active' => request()->is(ltrim($link['url'], '/')),
        ], $group['links']);

        return [
            'title' => $group[$lang],
            'url' => $group['url'] ?? null,
            'active' => in_array(true, array_column($links, 'active'), true),
            'links' => $links,
        ];
    }
}
