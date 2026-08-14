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

        $canonicalUrl = route('blog.show', $article->slug);
        $imageUrl = $article->featured_image
            ? (str_starts_with($article->featured_image, 'http') ? $article->featured_image : asset('storage/'.$article->featured_image))
            : ($article->service?->cover_image ?: asset('images/default-meta.png'));

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    '@id' => $canonicalUrl.'#blogpost',
                    'isPartOf' => [
                        '@type' => 'WebPage',
                        '@id' => $canonicalUrl,
                        'url' => $canonicalUrl,
                        'name' => ($article->meta_title ?: $article->title).' | Musoftware',
                    ],
                    'headline' => $article->title,
                    'description' => $article->meta_description ?: $article->excerpt,
                    'image' => array_filter([$imageUrl]),
                    'datePublished' => $article->published_at ? $article->published_at->toIso8601String() : $article->created_at->toIso8601String(),
                    'dateModified' => $article->updated_at ? $article->updated_at->toIso8601String() : $article->created_at->toIso8601String(),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $article->service?->seller?->name ?: 'Musoftware Writer',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Musoftware',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset('v8main/img/logo.png'),
                        ]
                    ],
                    'mainEntityOfPage' => $canonicalUrl,
                    'inLanguage' => $article->language ?: 'en',
                ]
            ]
        ];

        return Inertia::render('Public/Blog/Show', [
            'article' => $article,
        ])->withViewData([
            'meta' => [
                'title' => ($article->meta_title ?: $article->title).' | Musoftware',
                'description' => $article->meta_description ?: $article->excerpt,
                'image' => $imageUrl,
                'url' => $canonicalUrl,
                'schema_json' => $schemaJson,
            ],
        ]);
    }
}

