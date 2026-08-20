<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\CurrenciesExchange;
use App\Helpers\FinanceHelper;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of the blog articles publicly.
     */
    public function index()
    {
        $search = request('search');
        $locale = app()->getLocale();

        $articles = BlogArticle::with('service.seller')
            ->published()
            ->where('language', $locale)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderBy('published_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('public.blog.index', [
            'articles' => $articles,
            'title' => 'Technical Library & Engineering Insights | Musoftwares',
            'description' => 'Deep architectural insights, ERP design patterns, WhatsApp APIs, and scalable infrastructure articles from Musoftwares Studio.',
        ]);
    }

    /**
     * Display the specified blog article publicly.
     */
    public function show(string $slug)
    {
        $article = BlogArticle::with(['service.seller', 'service.packages.currency'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        if ($article->service) {
            $viewerCurrency = FinanceHelper::instance()->getViewerCurrency(request());
            $article->service->packages->transform(function ($package) use ($viewerCurrency) {
                if ($package->currency_id && $package->currency_id != $viewerCurrency->id) {
                    $package->price = CurrenciesExchange::RateToday(
                        $package->price,
                        $package->currency_id,
                        $viewerCurrency->id
                    );
                    $package->currency_id = $viewerCurrency->id;
                    $package->setRelation('currency', $viewerCurrency);
                }

                return $package;
            });
        }

        $imageUrl = $article->featured_image
            ? (str_starts_with($article->featured_image, 'http') ? $article->featured_image : asset('storage/'.$article->featured_image))
            : ($article->service?->cover_image ?: asset('images/default-meta.png'));

        return view('public.blog.show', [
            'article' => $article,
            'title' => ($article->meta_title ?: $article->title).' | Musoftwares',
            'description' => $article->meta_description ?: $article->excerpt,
            'image' => $imageUrl,
        ]);
    }
}

