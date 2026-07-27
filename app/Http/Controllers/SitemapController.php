<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\WebsiteService;
use Modules\Marketplace\Models\Service as MarketplaceService;
use Modules\Marketplace\Models\ServiceCategory as MarketplaceCategory;
use Modules\Marketplace\Models\ServiceLandingPage;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        $staticRoutes = [
            'home' => '1.0',
            'marketplace.services.index' => '0.9',
            'portfolio' => '0.8',
            'platforms' => '0.8',
            'platforms.crm' => '0.8',
            'platforms.erp' => '0.8',
            'platforms.cloud' => '0.8',
            'solutions' => '0.8',
            'solutions.healthcare' => '0.8',
            'solutions.education' => '0.8',
            'solutions.ecommerce' => '0.8',
            'solutions.real-estate' => '0.8',
            'solutions.finance' => '0.8',
            'company' => '0.7',
            'company.about' => '0.7',
            'company.careers' => '0.7',
            'company.contact' => '0.7',
            'legal.privacy' => '0.5',
            'legal.terms' => '0.5',
            'legal.cookies' => '0.5',
            'pricing' => '0.8',
        ];

        foreach ($staticRoutes as $route => $priority) {
            if (\Route::has($route)) {
                $loc = route($route);
                $urls[] = [
                    'loc' => $loc,
                    'ar_loc' => $loc.'?lang=ar',
                    'en_loc' => $loc.'?lang=en',
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
                        'ar_loc' => $loc.'?lang=ar',
                        'en_loc' => $loc.'?lang=en',
                        'lastmod' => optional($service->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.9',
                    ];
                }
            }
        }

        // Blog Articles
        if (class_exists(BlogArticle::class)) {
            $articles = BlogArticle::published()->get();
            foreach ($articles as $article) {
                if (\Route::has('blog.show') && ! empty($article->slug)) {
                    $loc = route('blog.show', $article->slug);
                    $urls[] = [
                        'loc' => $loc,
                        'ar_loc' => $loc.'?lang=ar',
                        'en_loc' => $loc.'?lang=en',
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
                        'ar_loc' => $loc.'?lang=ar',
                        'en_loc' => $loc.'?lang=en',
                        'lastmod' => optional($mpService->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.9',
                    ];
                }
            }
        }

        // Marketplace Categories
        if (class_exists(MarketplaceCategory::class)) {
            $categories = MarketplaceCategory::all();
            foreach ($categories as $cat) {
                if (\Route::has('marketplace.services.index') && ! empty($cat->slug)) {
                    $loc = route('marketplace.services.index', ['category' => $cat->slug]);
                    $urls[] = [
                        'loc' => $loc,
                        'ar_loc' => $loc.'?lang=ar',
                        'en_loc' => $loc.'?lang=en',
                        'lastmod' => optional($cat->updated_at)->toAtomString() ?: now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
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
                        'ar_loc' => $loc.'?lang=ar',
                        'en_loc' => $loc.'?lang=en',
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
