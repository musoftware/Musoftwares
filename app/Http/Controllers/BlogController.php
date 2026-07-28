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

        $articles = BlogArticle::with('service.seller')
            ->published()
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

        return Inertia::render('Public/Blog/Index', [
            'articles' => $articles,
            'filters' => [
                'search' => $search ?? '',
            ],
        ])->withViewData([
            'meta' => [
                'title' => 'Blog | Musoftware',
                'description' => 'Read the latest insights, tutorials, and updates from the Musoftware team.',
                'url' => url()->current(),
            ],
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

        return Inertia::render('Public/Blog/Show', [
            'article' => $article,
        ])->withViewData([
            'meta' => [
                'title' => ($article->meta_title ?: $article->title).' | Musoftware',
                'description' => $article->meta_description ?: $article->excerpt,
                'image' => $article->featured_image
                    ? (str_starts_with($article->featured_image, 'http') ? $article->featured_image : asset('storage/'.$article->featured_image))
                    : ($article->service?->cover_image ?: asset('images/default-meta.png')),
                'url' => url()->current(),
            ],
        ]);
    }
}

