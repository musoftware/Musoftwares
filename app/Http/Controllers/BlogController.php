<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\CurrenciesExchange;
use App\Helpers\FinanceHelper;
use App\Support\BlogSeo;
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

        $isAr = $locale === 'ar';

        return view('public.blog.index', [
            'articles' => $articles,
            'title' => $isAr ? 'المدونة | موسوفت' : 'Blog | Musoftware',
            'description' => $isAr
                ? 'مقالات عملية بالعربي عن اختيار برنامج الكاشير والمخزون، وتكلفة المواقع، والفاتورة الإلكترونية، وتسويق الأعمال في مصر.'
                : 'Practical guides on choosing POS and inventory software, website costs, the Egyptian e-receipt and business marketing.',
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
            ->first();

        $currentLocale = app()->getLocale();

        // Each language version has its own URL and is linked by hreflang, so an
        // existing article is always shown as is. Redirecting by the visitor's
        // language hid the Arabic articles from Google (it crawls in English).
        if ($article) {
            app()->setLocale($article->language === 'ar' ? 'ar' : 'en');
        } else {
            $translationArticle = BlogArticle::published()
                ->whereHas('translations', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                })
                ->where('language', $currentLocale)
                ->first();

            if (! $translationArticle) {
                $translationArticle = BlogArticle::published()
                    ->whereHas('translations', function ($q) use ($slug) {
                        $q->where('slug', $slug);
                    })
                    ->first();
            }

            if ($translationArticle) {
                return redirect()->route('blog.show', $translationArticle->slug, 301);
            }

            abort(404);
        }

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

        $seo = app(BlogSeo::class);

        return view('public.blog.show', [
            'article' => $article,
            'title' => ($article->meta_title ?: $article->title).' | Musoftware',
            'description' => $article->meta_description ?: $article->excerpt,
            'image' => $imageUrl,
            'canonical' => route('blog.show', $article->slug),
            'alternates' => $seo->alternates($article),
            'articleJson' => $seo->toJson($seo->articleGraph($article, $imageUrl)),
            'readingMinutes' => $seo->readingMinutes($article),
        ]);
    }
}

