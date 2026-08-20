<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Models\Project;
use App\Models\WebsiteService;
use Modules\Marketplace\Models\Service as MarketplaceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NewsAggregatorService
{
    /**
     * Get aggregated news and updates feed across Library, Marketplace, and Services.
     *
     * @param int $limitPerSource
     * @return array
     */
    public function getFeaturedNewsFeed(int $limitPerSource = 2): array
    {
        return Cache::remember('public_news_aggregated_feed_' . app()->getLocale(), 60, function () use ($limitPerSource) {
            $feed = [];

            // 1. Library (Blog Articles & Technical Reports)
            try {
                $articles = BlogArticle::where('is_published', true)
                    ->orderByDesc('published_at')
                    ->limit($limitPerSource)
                    ->get();

                foreach ($articles as $article) {
                    $feed[] = [
                        'id' => 'library-' . $article->id,
                        'source' => 'library',
                        'category' => 'Library / Technical Report',
                        'title' => $article->title,
                        'description' => $article->excerpt ?: Str::limit(strip_tags($article->content ?? ''), 130),
                        'image' => $article->featured_image ? asset($article->featured_image) : '/images/hero/hero_erp.jpg',
                        'link' => route('blog.show', $article->slug),
                        'linkText' => 'Read in Library ➔',
                        'badge' => 'Library',
                        'published_at' => $article->published_at ? $article->published_at->toIso8601String() : null,
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore if table missing in testing
            }

            // 2. Marketplace (Featured Plugins, Tools & Packages)
            try {
                $marketplaceItems = MarketplaceService::query()
                    ->where(function ($q) {
                        $q->where('status', 'approved')
                          ->orWhere('is_featured', true);
                    })
                    ->latest()
                    ->limit($limitPerSource)
                    ->get();

                foreach ($marketplaceItems as $item) {
                    $feed[] = [
                        'id' => 'marketplace-' . $item->id,
                        'source' => 'marketplace',
                        'category' => 'Marketplace / Active Release',
                        'title' => $item->title ?? 'Cloud Automation Tool',
                        'description' => $item->tagline ?: Str::limit(strip_tags($item->description ?? ''), 130),
                        'image' => $item->cover_image ?: '/images/hero/hero_meta.jpg',
                        'link' => route('marketplace.services.index') . '?q=' . urlencode($item->title ?? ''),
                        'linkText' => 'Explore in Marketplace ➔',
                        'badge' => 'Marketplace',
                        'published_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore if marketplace module is dormant
            }

            // 3. Bespoke Services & Case Studies (Enterprise Architecture)
            try {
                $services = WebsiteService::latest()->limit($limitPerSource)->get();

                if ($services->isNotEmpty()) {
                    foreach ($services as $service) {
                        $feed[] = [
                            'id' => 'service-' . $service->id,
                            'source' => 'services',
                            'category' => 'Studio / Architecture Service',
                            'title' => $service->title_en ?: $service->title_ar ?: 'Enterprise ERP Ledger',
                            'description' => $service->subtitle_en ?: $service->subtitle_ar ?: 'Sub-millisecond double-entry ledger and verified Meta API automations.',
                            'image' => $service->primary_image_en ?: '/images/hero/hero_fintech.jpg',
                            'link' => route('platforms.erp'),
                            'linkText' => 'View Service Architecture ➔',
                            'badge' => 'Enterprise Service',
                            'published_at' => $service->created_at ? $service->created_at->toIso8601String() : null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }

            // Always ensure at least the 3 primary architectural hero slides exist as high-fidelity anchors
            $curatedDefaults = [
                [
                    'id' => 'erp-core',
                    'source' => 'services',
                    'category' => 'Architecture Report',
                    'title' => 'The Future of Cloud ERP: 2026 Core Architecture',
                    'description' => 'Next-generation double-entry financial ledger, multi-tenant isolation, and sub-millisecond database pipelines.',
                    'image' => '/images/hero/hero_erp.jpg',
                    'link' => '/portfolio/revflow',
                    'linkText' => 'Read Case Study ➔',
                    'badge' => 'Enterprise ERP',
                ],
                [
                    'id' => 'meta-cloud',
                    'source' => 'services',
                    'category' => 'Cloud Innovation',
                    'title' => 'High-Throughput Meta Graph & WhatsApp Cloud Engine',
                    'description' => 'Distributed webhook dispatching and verified Meta API pipelines handling 1M+ daily transactions without dropped packets.',
                    'image' => '/images/hero/hero_meta.jpg',
                    'link' => '/platforms/crm',
                    'linkText' => 'Explore Platform ➔',
                    'badge' => 'Meta API',
                ],
                [
                    'id' => 'fintech-pos',
                    'source' => 'marketplace',
                    'category' => 'FinTech & Marketplace',
                    'title' => 'Commodity Exchange & Real-Time Gold POS Terminal',
                    'description' => 'Sub-second market price streaming, hardware printer synchronizers, and offline-first edge resilience.',
                    'image' => '/images/hero/hero_fintech.jpg',
                    'link' => '/marketplace/services',
                    'linkText' => 'Explore Marketplace ➔',
                    'badge' => 'Marketplace',
                ],
            ];

            // Merge dynamic feed before defaults
            $combined = array_merge($feed, $curatedDefaults);

            // Deduplicate by ID and slice
            $uniqueFeed = [];
            $seenIds = [];
            foreach ($combined as $item) {
                if (!in_array($item['id'], $seenIds)) {
                    $seenIds[] = $item['id'];
                    $uniqueFeed[] = $item;
                }
            }

            return array_slice($uniqueFeed, 0, 6);
        });
    }
}
