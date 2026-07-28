<?php

namespace Modules\Marketplace\Helpers;

use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;

class MarketplaceSchemaHelper
{
    /**
     * Generate Schema.org Organization structure.
     */
    public static function forOrganization(): array
    {
        $baseUrl = config('app.url', 'https://musoftwares.com');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Musoftwares Marketplace',
            'url' => $baseUrl . '/marketplace',
            'logo' => $baseUrl . '/v8main/img/logo.png',
            'description' => 'Digital services, SaaS solutions, and software marketplace.',
            'sameAs' => [
                'https://facebook.com/musoftwares',
                'https://twitter.com/musoftwares',
                'https://linkedin.com/company/musoftwares',
            ],
        ];
    }

    /**
     * Generate Schema.org Service / Product structure.
     */
    public static function forService(Service $service): array
    {
        $service->loadMissing(['seller', 'category', 'packages', 'reviews']);
        $baseUrl = config('app.url', 'https://musoftwares.com');
        $serviceUrl = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug]);

        $minPrice = 0;
        $maxPrice = 0;
        $offers = [];

        if ($service->packages && $service->packages->count() > 0) {
            $prices = $service->packages->pluck('price')->filter()->map(fn($p) => (float)$p)->values()->toArray();
            if (!empty($prices)) {
                $minPrice = min($prices);
                $maxPrice = max($prices);
            }

            foreach ($service->packages as $pkg) {
                $offers[] = [
                    '@type' => 'Offer',
                    'name' => $pkg->name ?? 'Standard Package',
                    'price' => (float)($pkg->price ?? 0),
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $serviceUrl,
                    'description' => $pkg->description ?? '',
                    'deliveryLeadTime' => [
                        '@type' => 'QuantitativeValue',
                        'value' => $pkg->delivery_days ?? 1,
                        'unitCode' => 'DAY'
                    ]
                ];
            }
        }

        if (empty($offers)) {
            $offers[] = [
                '@type' => 'Offer',
                'price' => (float)($service->price ?? 0),
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => $serviceUrl,
            ];
        }

        $ratingValue = 5.0;
        $reviewCount = 0;
        if ($service->reviews && $service->reviews->count() > 0) {
            $reviewCount = $service->reviews->count();
            $ratingValue = round($service->reviews->avg('rating'), 1);
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->title,
            'description' => strip_tags($service->description ?? $service->tagline ?? ''),
            'image' => $service->cover_image ? [$service->cover_image] : [],
            'category' => $service->category->name ?? 'Software Services',
            'provider' => [
                '@type' => 'Organization',
                'name' => $service->seller->name ?? 'Musoftwares',
            ],
            'url' => $serviceUrl,
            'offers' => count($offers) === 1 ? $offers[0] : [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'USD',
                'lowPrice' => $minPrice,
                'highPrice' => $maxPrice,
                'offerCount' => count($offers),
                'offers' => $offers,
            ]
        ];

        if ($reviewCount > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $ratingValue,
                'reviewCount' => $reviewCount,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        return $schema;
    }

    /**
     * Generate Schema.org BreadcrumbList.
     */
    public static function forBreadcrumbs(array $crumbs): array
    {
        $itemList = [];
        $position = 1;

        foreach ($crumbs as $crumb) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => $crumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }

    /**
     * Generate Schema.org FAQPage.
     */
    public static function forFaq(array $faqs): array
    {
        $questions = [];

        foreach ($faqs as $faq) {
            $q = is_array($faq) ? ($faq['question'] ?? $faq['q'] ?? '') : '';
            $a = is_array($faq) ? ($faq['answer'] ?? $faq['a'] ?? '') : '';

            if (!empty($q) && !empty($a)) {
                $questions[] = [
                    '@type' => 'Question',
                    'name' => $q,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($a),
                    ]
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Generate Schema.org Category / CollectionPage.
     */
    public static function forCategory(ServiceCategory $category, $services = []): array
    {
        $baseUrl = config('app.url', 'https://musoftwares.com');
        $itemList = [];
        $pos = 1;

        foreach ($services as $srv) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'url' => route('marketplace.services.show', ['id' => $srv->id, 'slug' => $srv->slug]),
                'name' => $srv->title,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->description ?? 'Browse ' . $category->name . ' services on Musoftwares Marketplace.',
            'url' => route('marketplace.services.index', ['category' => $category->slug ?? $category->id]),
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => count($itemList),
                'itemListElement' => $itemList,
            ]
        ];
    }
}
