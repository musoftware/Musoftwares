<?php

namespace App\Support;

/**
 * The stores we built and run, for the "Our stores" menu. Each one is a working sample of what we
 * can build for a client, so the names and short lines live here once.
 */
class SiteStores
{
    private const STORES = [
        'marketplace' => [
            'url' => '/marketplace/catalog',
            'match' => ['marketplace', 'marketplace/*'],
            'image' => '/images/menu/store-marketplace.webp',
            'ar' => 'شغلانة',
            'en' => 'Shoghlana',
            'line_ar' => 'سوق خدمات برمجة وتصميم. فلوسك محفوظة لحد ما تستلم.',
            'line_en' => 'A market for software and design gigs. Your money is held until you accept.',
        ],
        'shop' => [
            'url' => '/store',
            'match' => ['store', 'store/*'],
            'image' => '/images/menu/store-shop.webp',
            'line_ar' => 'ملابس وأحذية وحاجات للبيت، والدفع عند الاستلام.',
            'line_en' => 'Clothes, shoes and home goods, paid in cash on delivery.',
        ],
        'programs' => [
            'url' => '/services',
            'match' => ['services', 'services/*'],
            'image' => '/images/menu/store-programs.webp',
            'ar' => 'البرامج الجاهزة',
            'en' => 'Ready programs',
            'line_ar' => 'برامج كمبيوتر وأنظمة إدارة تشتغل من النهارده.',
            'line_en' => 'Desktop programs and business systems you can use today.',
        ],
        'micro' => [
            'url' => '/micro-services',
            'match' => ['micro-services'],
            'image' => '/images/menu/store-micro.webp',
            'ar' => 'الخدمات المصغرة',
            'en' => 'Micro services',
            'line_ar' => 'شغل صغير بسعر ثابت ومدة معروفة، من حسابك.',
            'line_en' => 'Small jobs at a fixed price and time, from your account.',
        ],
    ];

    public static function name(string $key, ?string $lang = null): string
    {
        $lang ??= app()->getLocale() === 'ar' ? 'ar' : 'en';

        if ($key === 'shop') {
            return (string) config($lang === 'ar' ? 'shop.name' : 'shop.name_en');
        }

        return self::STORES[$key][$lang];
    }

    /** @return list<array{key: string, name: string, line: string, url: string, image: string, active: bool}> */
    public static function cards(string $lang): array
    {
        return array_map(fn (string $key) => [
            'key' => $key,
            'name' => self::name($key, $lang),
            'line' => self::STORES[$key]['line_'.$lang],
            'url' => self::STORES[$key]['url'],
            'image' => self::STORES[$key]['image'],
            'active' => request()->is(...self::STORES[$key]['match']),
        ], array_keys(self::STORES));
    }
}
