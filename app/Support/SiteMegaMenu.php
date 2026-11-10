<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;

/**
 * Everything we offer, for the nested "Our services" menu: a short list of groups, each opening its
 * own links. Custom work comes first because it is the main service; the rest are samples of it.
 * Products and free tools are read from their real source, so a new one shows without touching this file.
 */
class SiteMegaMenu
{
    private const PRODUCTS_CACHE_KEY = 'site_nav_products';

    private const PRODUCTS_CACHE_SECONDS = 600;

    /** A group longer than this shows its first links and a "see all" link, so no panel gets too long. */
    private const MAX_LINKS = 10;

    private const CUSTOM = ['key' => 'custom', 'ar' => 'برمجة على مقاس شغلك', 'en' => 'Software made for your business', 'links' => [
        ['url' => '/start-project', 'ar' => 'ابدأ مشروعك خطوة بخطوة', 'en' => 'Start your project step by step'],
        ['url' => '/estimator', 'ar' => 'احسب التكلفة', 'en' => 'Cost estimator'],
        ['url' => '/how-we-work', 'ar' => 'إزاي بنشتغل', 'en' => 'How we work'],
        ['url' => '/portfolio', 'ar' => 'أعمالنا', 'en' => 'Our work'],
        ['url' => '/white-label', 'ar' => 'بيع برامجنا باسمك', 'en' => 'Sell our programs under your name'],
    ]];

    private const STORE_GROUPS = [
        'services' => ['url' => '/services#services', 'ar' => 'مواقع وتسويق', 'en' => 'Websites and marketing'],
        'systems' => ['url' => '/services#systems', 'ar' => 'أنظمة إدارة جاهزة', 'en' => 'Ready business systems'],
        'programs' => ['url' => '/services#programs', 'ar' => 'برامج للكمبيوتر', 'en' => 'Desktop programs'],
    ];

    /** Groups that come after the products and tools. */
    private const STATIC_GROUPS = [
        ['key' => 'account', 'ar' => 'من حسابك', 'en' => 'In your account', 'links' => [
            ['url' => '/marketplace/services/create', 'ar' => 'اعرض خدمتك للبيع', 'en' => 'Sell your service'],
            ['url' => '/marketplace/orders', 'ar' => 'طلباتي', 'en' => 'My orders'],
            ['url' => '/sms-payment-gateway', 'ar' => 'بوابة الدفع', 'en' => 'Payment gateway'],
            ['url' => '/whatsapp-sender', 'ar' => 'إرسال واتساب', 'en' => 'WhatsApp sender'],
            ['url' => '/my-licenses', 'ar' => 'تراخيص برامجي', 'en' => 'My licenses'],
            ['url' => '/subscriptions/plans', 'ar' => 'خطط الاشتراك', 'en' => 'Subscription plans'],
            ['url' => '/referrals', 'ar' => 'اكسب من الإحالات', 'en' => 'Earn from referrals'],
            ['url' => '/tickets', 'ar' => 'الدعم الفني', 'en' => 'Support tickets'],
        ]],
        ['key' => 'company', 'ar' => 'عن موسوفت', 'en' => 'About Musoftware', 'links' => [
            ['url' => '/about/mahmoud-amin', 'ar' => 'من نحن', 'en' => 'About us'],
            ['url' => '/company/contact', 'ar' => 'تواصل معنا', 'en' => 'Contact us'],
            ['url' => '/blog', 'ar' => 'المدونة', 'en' => 'Blog'],
            ['url' => '/library', 'ar' => 'المكتبة الرقمية', 'en' => 'Digital library'],
            ['url' => '/compare/laravel-vs-nodejs', 'ar' => 'المقارنات والمقالات', 'en' => 'Comparisons and articles'],
            ['url' => '/listing', 'ar' => 'لوحة الوظائف', 'en' => 'Job board'],
            ['url' => '/company/careers', 'ar' => 'وظائف في موسوفت', 'en' => 'Careers at Musoftware'],
        ]],
    ];

    /** @return list<array{key: string, title: string, url: ?string, active: bool, links: list<array{url: string, label: string, active: bool}>, more: ?array{url: string, label: string}}> */
    public static function groups(string $lang): array
    {
        return [
            self::shapeGroup(self::CUSTOM, $lang),
            ...self::storeGroups($lang),
            self::shapeGroup(['key' => 'tools', 'url' => '/services/tools', 'ar' => 'أدوات مجانية', 'en' => 'Free tools', 'links' => self::toolLinks()], $lang),
            ...array_map(fn (array $group) => self::shapeGroup($group, $lang), self::STATIC_GROUPS),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function storeGroups(string $lang): array
    {
        $products = collect(self::products())->groupBy('group');

        $groups = array_map(fn (string $key) => self::shapeGroup([
            ...self::STORE_GROUPS[$key],
            'key' => $key,
            'links' => $products->get($key, collect())->map(fn (array $product) => [
                'url' => '/services/'.$product['slug'],
                'ar' => $product['ar'],
                'en' => $product['en'],
            ])->all(),
        ], $lang), array_keys(self::STORE_GROUPS));

        return array_values(array_filter($groups, fn (array $group) => $group['links'] !== []));
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

    /** @param array{key: string, url?: string, ar: string, en: string, links: array<int, array{url: string, ar: string, en: string}>} $group */
    private static function shapeGroup(array $group, string $lang): array
    {
        $links = array_map(fn (array $link) => [
            'url' => $link['url'],
            'label' => $link[$lang],
            'active' => request()->is(ltrim($link['url'], '/')),
        ], $group['links']);
        $url = $group['url'] ?? null;
        $isLong = $url !== null && count($links) > self::MAX_LINKS;

        return [
            'key' => $group['key'],
            'title' => $group[$lang],
            'url' => $url,
            'active' => in_array(true, array_column($links, 'active'), true),
            'links' => $isLong ? array_slice($links, 0, self::MAX_LINKS) : $links,
            'more' => $isLong ? ['url' => $url, 'label' => ($lang === 'ar' ? 'شوف الكل' : 'See all').' ('.count($links).')'] : null,
        ];
    }
}
