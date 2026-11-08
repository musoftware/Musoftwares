<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\ShopSeo;

class StorefrontController extends Controller
{
    private const SORTS = [
        'featured' => [['sort_order', 'asc'], ['id', 'desc']],
        'newest' => [['id', 'desc']],
        'price_low' => [['price', 'asc'], ['id', 'desc']],
        'price_high' => [['price', 'desc'], ['id', 'desc']],
    ];

    public function __construct(private readonly ShopSeo $seo) {}

    public function home(): View
    {
        $seo = $this->seo->page(config('shop.tagline'), [
            'title' => config('shop.name').' | '.config('shop.tagline'),
            'canonical' => route('shop.home'),
            'schemas' => [$this->seo->website()],
        ]);

        return view('shop::home', [
            'seo' => $seo,
            'categories' => ShopCategory::visible()->get(),
            'products' => ShopProduct::visible()->orderBy('sort_order')->latest('id')->limit(12)->get(),
        ]);
    }

    public function collection(Request $request, ShopCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $sort = $this->sortKey($request);
        $products = $this->paginate($category->products()->getQuery()->visible(), $sort);
        abort_if($products->isEmpty() && $products->currentPage() > 1, 404);

        return view('shop::collection', [
            'seo' => $this->collectionSeo($category, $products, $sort),
            'category' => $category,
            'products' => $products,
            'sort' => $sort,
        ]);
    }

    public function search(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $sort = $this->sortKey($request);
        $query = ShopProduct::visible()->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
        $products = $term === '' ? null : $this->paginate($query, $sort);

        return view('shop::search', [
            'seo' => $this->seo->privatePage('البحث'),
            'term' => $term,
            'products' => $products,
            'sort' => $sort,
        ]);
    }

    public function product(ShopProduct $product): View
    {
        abort_unless($product->is_active, 404);
        $product->load('category');

        return view('shop::product', [
            'seo' => $this->productSeo($product),
            'product' => $product,
            'related' => $this->related($product),
        ]);
    }

    private function sortKey(Request $request): string
    {
        $sort = (string) $request->query('sort');

        return array_key_exists($sort, self::SORTS) ? $sort : 'featured';
    }

    private function paginate(Builder $query, string $sort): LengthAwarePaginator
    {
        foreach (self::SORTS[$sort] as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        return $query->paginate((int) config('shop.per_page'))->withQueryString();
    }

    /** A sorted list shows the same products, so only the default order is indexed. */
    private function collectionSeo(ShopCategory $category, LengthAwarePaginator $products, string $sort): array
    {
        $page = $products->currentPage();
        $canonical = $page > 1 ? $category->url().'?page='.$page : $category->url();
        $suffix = $page > 1 ? ' - صفحة '.$page : '';
        $robots = $sort === 'featured' ? 'index,follow,max-image-preview:large' : 'noindex,follow';

        return $this->seo->page($category->name.$suffix, [
            'description' => $this->seo->excerpt($category->description) ?: $category->name.' من '.config('shop.name').'. '.config('shop.announcement').'.',
            'canonical' => $canonical,
            'robots' => $robots,
            'image' => $category->imageUrl() ?? $products->first()?->mainImageUrl(),
            'schemas' => [
                $this->seo->collection($category, $products->getCollection()),
                $this->seo->breadcrumbs([config('shop.name') => route('shop.home'), $category->name => $category->url()]),
            ],
        ]);
    }

    private function productSeo(ShopProduct $product): array
    {
        $trail = [config('shop.name') => route('shop.home')];
        if ($product->category?->is_active) {
            $trail[$product->category->name] = $product->category->url();
        }
        $trail[$product->name] = $product->url();

        return $this->seo->page($product->seo_title ?: $product->name, [
            'description' => $product->seo_description ?: ($this->seo->excerpt($product->description) ?: $product->name),
            'canonical' => $product->url(),
            'image' => $product->mainImageUrl(),
            'type' => 'product',
            'schemas' => [$this->seo->product($product), $this->seo->breadcrumbs($trail)],
        ]);
    }

    private function related(ShopProduct $product)
    {
        return ShopProduct::visible()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->latest('id')
            ->limit(4)
            ->get();
    }
}
