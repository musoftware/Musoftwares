<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\WebsiteService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Models\Service as MarketplaceService;
use Modules\Marketplace\Models\ServiceCategory as MarketplaceCategory;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;

/**
 * /sitemap.xml is a sitemap index (as large marketplaces do) that points to small parts:
 * pages, marketplace, and the blog in pages of BLOG_PAGE_SIZE articles. Each part stays small and
 * fast, so one slow or broken part never takes the whole sitemap down.
 */
class SitemapController extends Controller
{
    public const BLOG_PAGE_SIZE = 1000;

    public function index(): Response
    {
        $parts = [
            ['name' => 'pages', 'lastmod' => now()->startOfDay()],
            ['name' => 'marketplace', 'lastmod' => $this->latest(MarketplaceService::class)],
        ];

        $blogPages = (int) ceil($this->publishedArticles()->count() / self::BLOG_PAGE_SIZE);
        for ($page = 1; $page <= $blogPages; $page++) {
            $parts[] = ['name' => 'blog-'.$page, 'lastmod' => $this->latest(BlogArticle::class)];
        }

        return response()->view('sitemap-index', ['parts' => $parts])->header('Content-Type', 'application/xml');
    }

    public function part(string $name): Response
    {
        if ($name === 'pages') {
            $urls = $this->pageUrls();
        } elseif ($name === 'marketplace') {
            $urls = $this->marketplaceUrls();
        } elseif (preg_match('/^blog-([1-9]\d*)$/', $name, $m)) {
            $urls = $this->blogUrls((int) $m[1]);
            abort_if($urls === [], 404);
        } else {
            abort(404);
        }

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    /** Fixed pages, website services, store products and free tools. */
    private function pageUrls(): array
    {
        $urls = [];
        $today = now()->startOfDay();

        $staticRoutes = [
            'home' => '1.0',
            'marketplace.services.index' => '0.9',
            'services.index' => '0.9',
            'portfolio' => '0.8',
            'how-we-work' => '0.9',
            'white-label' => '0.7',
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
            if (Route::has($route)) {
                $daily = in_array($route, ['home', 'marketplace.services.index'], true);
                $urls[] = $this->url(route($route), $today, $daily ? 'daily' : 'weekly', $priority);
            }
        }

        if (class_exists(WebsiteService::class) && Route::has('website-services.show')) {
            foreach (WebsiteService::all() as $service) {
                if (! empty($service->slug)) {
                    $urls[] = $this->url(route('website-services.show', $service->slug), $service->updated_at, 'weekly', '0.9');
                }
            }
        }

        if (class_exists(SoftwareProduct::class)) {
            foreach (SoftwareProduct::where('is_active', true)->orderBy('sort_order')->get() as $product) {
                $urls[] = $this->url($product->purchaseUrl(), $product->updated_at, 'weekly', '0.9');
            }
        }

        if (Route::has('services.tools.show')) {
            foreach (FreeTools::slugs() as $slug) {
                $urls[] = $this->url(FreeTools::url($slug), $today, 'monthly', '0.8');
            }
        }

        return $urls;
    }

    /** Active marketplace services, category pages and seller landing pages. */
    private function marketplaceUrls(): array
    {
        $urls = [];

        if (class_exists(MarketplaceService::class) && Route::has('marketplace.services.show')) {
            foreach (MarketplaceService::where('status', 'active')->latest()->get(['id', 'slug', 'updated_at']) as $service) {
                $loc = route('marketplace.services.show', ['id' => $service->id, 'slug' => $service->slug ?: 'service']);
                $urls[] = $this->url($loc, $service->updated_at, 'daily', '0.9');
            }
        }

        // category pages of their own, not the ?category= filter of the catalog
        if (class_exists(MarketplaceCategory::class) && Route::has('marketplace.categories.show')) {
            foreach (MarketplaceCategory::whereNotNull('slug')->get() as $category) {
                $urls[] = $this->url(route('marketplace.categories.show', ['slug' => $category->slug]), $category->updated_at, 'weekly', '0.8');
            }
        }

        if (class_exists(ServiceLandingPage::class) && Route::has('services.landing-page.show')) {
            foreach (ServiceLandingPage::where('is_active', true)->whereNull('parent_variant_id')->get() as $page) {
                if (! empty($page->slug)) {
                    $urls[] = $this->url(route('services.landing-page.show', ['slug' => $page->slug]), $page->updated_at, 'weekly', '0.9');
                }
            }
        }

        return $urls;
    }

    /** One page of published articles, each linked to its language versions. */
    private function blogUrls(int $page): array
    {
        if (! Route::has('blog.show')) {
            return [];
        }

        // only the columns needed, never the article bodies
        $articles = $this->publishedArticles()
            ->orderBy('id')
            ->forPage($page, self::BLOG_PAGE_SIZE)
            ->get(['id', 'slug', 'language', 'group_id', 'updated_at']);

        $groupIds = $articles->pluck('group_id')->filter()->unique()->values();
        $groups = $groupIds->isEmpty() ? collect() : $this->publishedArticles()
            ->whereIn('group_id', $groupIds)
            ->get(['slug', 'language', 'group_id'])
            ->filter(fn (BlogArticle $a) => filled($a->language))
            ->groupBy('group_id');

        return $articles->map(function (BlogArticle $article) use ($groups) {
            $loc = route('blog.show', $article->slug);
            $languages = filled($article->group_id) && isset($groups[$article->group_id])
                ? $groups[$article->group_id]->mapWithKeys(fn (BlogArticle $a) => [$a->language => route('blog.show', $a->slug)])->all()
                : (filled($article->language) ? [$article->language => $loc] : []);

            return $this->url($loc, $article->updated_at, 'weekly', '0.8')
                + ['alternates' => $languages + ['x-default' => $languages['en'] ?? $loc]];
        })->all();
    }

    private function publishedArticles(): Builder
    {
        return BlogArticle::published()->whereNotNull('slug')->where('slug', '!=', '');
    }

    private function latest(string $model): Carbon
    {
        $latest = class_exists($model) ? $model::max('updated_at') : null;

        return $latest ? Carbon::parse($latest) : now();
    }

    private function url(string $loc, $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => ($lastmod ? Carbon::parse($lastmod) : now())->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
