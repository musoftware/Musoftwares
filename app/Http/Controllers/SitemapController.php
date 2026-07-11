<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\WebsiteService;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        $staticRoutes = [
            'home', 'portfolio', 'platforms', 'platforms.crm', 'platforms.erp', 'platforms.cloud',
            'solutions', 'solutions.healthcare', 'solutions.education', 'solutions.ecommerce',
            'solutions.real-estate', 'solutions.finance', 'company', 'company.about',
            'company.careers', 'company.contact', 'legal.privacy', 'legal.terms', 'legal.cookies',
            'pricing',
        ];

        foreach ($staticRoutes as $route) {
            $urls[] = [
                'loc' => route($route),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $route === 'home' ? '1.0' : '0.8',
            ];
        }

        // Website Services
        $services = WebsiteService::all();
        foreach ($services as $service) {
            $urls[] = [
                'loc' => route('website-services.show', $service->slug),
                'lastmod' => $service->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ];
        }

        // Blog Articles
        $articles = BlogArticle::published()->get();
        foreach ($articles as $article) {
            $urls[] = [
                'loc' => route('blog.show', $article->slug),
                'lastmod' => $article->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        return response()->view('sitemap', [
            'urls' => $urls,
        ])->header('Content-Type', 'text/xml');
    }
}
