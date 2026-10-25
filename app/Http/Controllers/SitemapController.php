<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\WebsiteService;
use App\Support\BlogSeo;
use Modules\Marketplace\Models\Service as MarketplaceService;
use Modules\Marketplace\Models\ServiceCategory as MarketplaceCategory;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\SoftwareStore\Models\SoftwareProduct;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        $staticRoutes = [
            'home' => '1.0',
            'marketplace.services.index' => '0.9',
            'services.index' => '0.9',
            'portfolio' => '0.8',
            'platforms' => '0.8',
            'platforms.crm' => '0.8',
            'platforms.erp' => '0.8',
            'solutions' => '0.8',
            'company.about' => '0.7',
            'company.careers' => '0.7',
            'company.contact' => '0.7',
            'legal.privacy' => '0.5',
            'legal.terms' => '0.5',
            'legal.cookies' => '0.5',
            'estimator' => '0.8',
            'blog.index' => '0.8',
        ];

        foreach ($staticRoutes as $route => $priority) {
            if (\Route::has($route)) {
                $loc = route($route);
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => now()->startOfDay()->toAtomString(),
                    'changefreq' => $route === 'home' || $route === 'marketplace.services.index' ? 'daily' : 'weekly',
                    'priority' => $priority,
                ];
            }
        }

        // Website Services
        if (class_exists(WebsiteService::class)) {
            $services = WebsiteService::all();
            foreach ($services as $service) {
                if (\Route::has('website-services.show') && ! empty($service->slug)) {
                    $loc = route('website-services.show', $service->slug);
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => optional($service->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.9',
                    ];
                }
            }
        }

        // Programs and services of the software store
        if (class_exists(SoftwareProduct::class)) {
            foreach (SoftwareProduct::where('is_active', true)->orderBy('sort_order')->get() as $product) {
                $loc = $product->purchaseUrl();
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($product->updated_at)->toAtomString() ?: now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ];
            }
        }

        // Blog Articles
        if (class_exists(BlogArticle::class)) {
            $articles = BlogArticle::published()->get();
            foreach ($articles as $article) {
                if (\Route::has('blog.show') && ! empty($article->slug)) {
                    $loc = route('blog.show', $article->slug);
                    $languages = app(BlogSeo::class)->alternates($article)->all();
                    $urls[] = [
                        'loc' => $loc,
                        'alternates' => $languages + ['x-default' => $languages['en'] ?? $loc],
                        'lastmod' => optional($article->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            }
        }

        // Marketplace Active Services
        if (class_exists(MarketplaceService::class)) {
            $mpServices = MarketplaceService::where('status', 'active')->latest()->get();
            foreach ($mpServices as $mpService) {
                if (\Route::has('marketplace.services.show')) {
                    $loc = route('marketplace.services.show', ['id' => $mpService->id, 'slug' => $mpService->slug ?: 'service']);
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => optional($mpService->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.9',
                    ];
                }
            }
        }

        // Marketplace Categories (their own pages, not the ?category= filter of the catalog)
        if (class_exists(MarketplaceCategory::class) && \Route::has('marketplace.categories.show')) {
            foreach (MarketplaceCategory::whereNotNull('slug')->get() as $cat) {
                $loc = route('marketplace.categories.show', ['slug' => $cat->slug]);
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($cat->updated_at)->toAtomString() ?: now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
        }

        // Published Seller Public Landing Pages
        if (class_exists(ServiceLandingPage::class)) {
            $landingPages = ServiceLandingPage::where('is_active', true)
                ->whereNull('parent_variant_id')
                ->get();

            foreach ($landingPages as $lp) {
                if (\Route::has('services.landing-page.show') && ! empty($lp->slug)) {
                    $loc = route('services.landing-page.show', ['slug' => $lp->slug]);
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => optional($lp->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.9',
                    ];
                }
            }
        }

        return response()->view('sitemap', [
            'urls' => $urls,
        ])->header('Content-Type', 'text/xml');
    }
}
