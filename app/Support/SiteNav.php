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
