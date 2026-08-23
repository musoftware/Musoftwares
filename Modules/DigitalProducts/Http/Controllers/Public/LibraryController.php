<?php

namespace Modules\DigitalProducts\Http\Controllers\Public;

use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Modules\DigitalProducts\Models\DigitalCategory;
use Modules\DigitalProducts\Models\DigitalProduct;

class LibraryController extends Controller
{
    /**
     * Display the digital library storefront / catalog.
     */
    public function index(Request $request): View
    {
        $viewerCurrency = FinanceHelper::instance()->getViewerCurrency($request);

        $query = DigitalProduct::with(['category', 'currency'])
            ->where('is_published', true);

        // Search query
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $categorySlug = $request->category;
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Type filter (free / paid)
        if ($request->filled('type')) {
            if ($request->type === 'free') {
                $query->where('is_free', true);
            } elseif ($request->type === 'paid') {
                $query->where('is_free', false);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'popular' => $query->orderBy('download_count', 'desc'),
            'views' => $query->orderBy('view_count', 'desc'),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        // Attach converted currency prices
        $products->getCollection()->transform(function (DigitalProduct $p) use ($viewerCurrency) {
            $p->converted_price = $p->getPriceInCurrency($viewerCurrency->id);
            $p->viewer_price_formatted = $p->formatPriceInCurrency($viewerCurrency);
            $p->usd_price_formatted = $p->formatPriceInUsd();
            return $p;
        });

        $categories = DigitalCategory::where('is_active', true)
            ->withCount('publishedProducts')
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = DigitalProduct::with(['category', 'currency'])
            ->where('is_published', true)
            ->where('is_featured', true)
            ->take(4)
            ->get()
            ->transform(function (DigitalProduct $p) use ($viewerCurrency) {
                $p->converted_price = $p->getPriceInCurrency($viewerCurrency->id);
                $p->viewer_price_formatted = $p->formatPriceInCurrency($viewerCurrency);
                $p->usd_price_formatted = $p->formatPriceInUsd();
                return $p;
            });

        $meta = [
            'title' => 'المكتبة الرقمية | كتب وأدلة إلكترونية حصرية - Musoftware',
            'description' => 'تصفح وحمّل أفضل الكتب الرقمية والأدلة التطبيقية في البرمجة، إدارة الأعمال، الذكاء الاصطناعي، والتسويق مجاناً ومدفوعاً.',
            'url' => route('library.index'),
            'type' => 'website',
        ];

        return view('digitalproducts::public.index', compact('products', 'categories', 'featuredProducts', 'viewerCurrency', 'meta'));
    }

    /**
     * Display a single digital product / book details.
     */
    public function show(Request $request, string $slug): View
    {
        $viewerCurrency = FinanceHelper::instance()->getViewerCurrency($request);

        $product = DigitalProduct::with(['category', 'currency'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Increment view count safely
        $product->increment('view_count');

        $product->converted_price = $product->getPriceInCurrency($viewerCurrency->id);
        $product->viewer_price_formatted = $product->formatPriceInCurrency($viewerCurrency);
        $product->usd_price_formatted = $product->formatPriceInUsd();

        // Determine user wallet currency for accurate purchasing feedback
        $userCurrency = (auth()->check() && auth()->user()->currency_id)
            ? (Currency::find(auth()->user()->currency_id) ?? $viewerCurrency)
            : $viewerCurrency;

        $userPriceFormatted = $product->formatPriceInCurrency($userCurrency);
        $userConvertedPrice = $product->getPriceInCurrency($userCurrency->id);

        // Related products in same category
        $relatedProducts = DigitalProduct::with(['category', 'currency'])
            ->where('is_published', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn($q) => $q->where('category_id', $product->category_id))
            ->take(4)
            ->get()
            ->transform(function (DigitalProduct $p) use ($viewerCurrency) {
                $p->converted_price = $p->getPriceInCurrency($viewerCurrency->id);
                $p->viewer_price_formatted = $p->formatPriceInCurrency($viewerCurrency);
                $p->usd_price_formatted = $p->formatPriceInUsd();
                return $p;
            });

        $isPurchased = auth()->check() ? $product->isPurchasedBy(auth()->user()) : false;

        $meta = [
            'title' => ($product->meta_title ?: $product->title) . ' | مكتبة Musoftware',
            'description' => $product->meta_description ?: ($product->short_description ?: Str::limit(strip_tags($product->description), 160)),
            'image' => $product->cover_url,
            'url' => route('library.show', $product->slug),
            'type' => 'book',
        ];

        return view('digitalproducts::public.show', compact(
            'product',
            'relatedProducts',
            'isPurchased',
            'viewerCurrency',
            'userCurrency',
            'userPriceFormatted',
            'userConvertedPrice',
            'meta'
        ));
    }
}
