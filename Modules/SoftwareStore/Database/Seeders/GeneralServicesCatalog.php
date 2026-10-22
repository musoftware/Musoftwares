<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Productized services (websites, stores, marketing, branding, AI chatbots) sold at /services with fixed-scope plans.
 * Each service has three plans. Prices are in EGP and sit in the low to middle part of the ranges found in the
 * market research (Oct 2026). They are asking prices, not quotes: the owner confirms them before the pages go live.
 * The copy promises only the listed work. It never promises rankings, followers, sales or ad results.
 */
class GeneralServicesCatalog
{
    private const FIRST_SORT_ORDER = 20;

    /** @return array<int, array<string, mixed>> */
    public static function programs(): array
    {
        $services = [...GeneralServicesWeb::services(), ...GeneralServicesMarketing::services()];

        return array_map(fn (array $service, int $index) => self::build($service, self::FIRST_SORT_ORDER + $index), $services, array_keys($services));
    }

    private static function build(array $service, int $order): array
    {
        return [
            'slug' => $service['slug'],
            'sort_order' => $order,
            'image_path' => 'images/software/'.$service['slug'].'.svg',
            'name_en' => $service['name_en'],
            'name_ar' => $service['name_ar'],
            'tagline_en' => $service['tagline_en'],
            'tagline_ar' => $service['tagline_ar'],
            'description_en' => $service['tagline_en'],
            'description_ar' => $service['tagline_ar'],
            'currency' => 'EGP',
            'plans' => $service['plans'],
            'theme' => [
                'colors' => $service['colors'],
                'sections' => ['problems', 'pillars', 'video', 'steps', 'gallery', 'plans'],
            ],
            'landing' => ['ar' => self::copy($service, 'ar'), 'en' => self::copy($service, 'en')],
        ];
    }

    private static function copy(array $service, string $lang): array
    {
        $t = $service[$lang];
        $common = GeneralServicesCommon::for($lang, $service['billing']);

        return [
            'kicker' => $t['kicker'],
            'headline' => $t['headline'],
            'subheadline' => $t['subheadline'],
            'terms' => $common['terms'],
            'overview' => ['title' => $common['overview_title'], 'items' => [
                ['title' => $common['who'], 'body' => $t['who']],
                ['title' => $common['need'], 'body' => $t['need']],
                ['title' => $common['after'], 'body' => $common['after_body']],
            ]],
            'problems' => ['kicker' => $common['problems_kicker'], 'title' => $t['problems_title'], 'items' => $t['problems']],
            'pillars' => ['kicker' => $common['pillars_kicker'], 'title' => $t['pillars_title'], 'items' => $t['pillars']],
            'video' => ['kicker' => $common['video_kicker'], 'title' => $common['video_title']],
            'steps' => ['kicker' => $common['steps_kicker'], 'title' => $common['steps_title'], 'items' => $common['steps']],
            'gallery' => ['title' => $common['gallery_title']],
            'plans' => [
                'kicker' => $common['plans_kicker'], 'title' => $common['plans_title'], 'period' => $service['period'][$lang],
                'included' => $common['included'], 'note' => $common['plans_note'].' '.$t['excludes'],
            ],
            'details' => ['title' => $common['details_title'], 'paragraphs' => $t['paragraphs'], 'list_title' => $common['list_title'], 'list' => $t['list']],
            'faq' => ['title' => $common['faq_title'], 'items' => array_merge($t['faq'], $common['faq'])],
            'cta' => ['kicker' => $common['cta_kicker'], 'title' => $t['cta_title'], 'body' => $common['cta_body'], 'button' => $common['cta_button']],
        ];
    }
}
