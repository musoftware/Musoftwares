<?php

namespace Modules\Shop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shop\Http\Requests\ShopProductRequest;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\ProductImages;
use Modules\Shop\Support\UniqueSlug;

class AdminShopProductController extends Controller
{
    public function __construct(private readonly ProductImages $images) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $needsPrice = $request->boolean('needs_price');

        $products = ShopProduct::with('category:id,name')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($needsPrice, fn ($query) => $query->where('angazny_needs_price', true))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ShopProduct $product) => $this->row($product));

        return Inertia::render('Admin/Shop/Products', [
            'products' => $products,
            'search' => $search,
            'needsPrice' => $needsPrice,
            'needsPriceCount' => ShopProduct::where('angazny_needs_price', true)->count(),
            'importReport' => session('shop_import'),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function edit(ShopProduct $product): Response
    {
        return $this->form($product);
    }

    public function store(ShopProductRequest $request): RedirectResponse
    {
        $this->save($request, new ShopProduct);

        return redirect()->route('admin.shop.products.index');
    }

    public function update(ShopProductRequest $request, ShopProduct $product): RedirectResponse
    {
        $this->save($request, $product);

        return redirect()->route('admin.shop.products.index');
    }

    public function toggle(ShopProduct $product): RedirectResponse
    {
        if (! $product->is_active && $product->mainImageUrl() === null) {
            return back()->withErrors(['images_add' => 'أضف صورة للمنتج الأول عشان يظهر في المتجر']);
        }

        if (! $product->is_active && $product->angazny_needs_price) {
            return back()->withErrors(['images_add' => 'حدد سعر البيع للمنتج الأول عشان يظهر في المتجر']);
        }

        $product->update(['is_active' => ! $product->is_active]);

        return back();
    }

    public function destroy(ShopProduct $product): RedirectResponse
    {
        $product->delete();

        return back();
    }

    private function save(ShopProductRequest $request, ShopProduct $product): void
    {
        $data = $request->safe()->except(['images_add', 'images_remove', 'slug']);

        $product->fill($data);
        $product->angazny_needs_price = $product->angazny_needs_price && ! $product->isDirty('price');
        $product->is_active = $product->is_active && ! $product->angazny_needs_price;
        $product->slug = UniqueSlug::make(ShopProduct::class, $request->input('slug') ?: $data['name'], $product->id);
        $product->images = $this->images->update(
            $product->images ?? [],
            $request->input('images_remove', []),
            $request->file('images_add', []),
        );
        $product->save();
    }

    private function form(?ShopProduct $product): Response
    {
        return Inertia::render('Admin/Shop/ProductForm', [
            'product' => $product ? $product->toArray() + ['image_urls' => $product->imageUrls(), 'url' => $product->url()] : null,
            'categories' => ShopCategory::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function row(ShopProduct $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'price' => $product->price,
            'image_url' => $product->mainImageUrl(),
            'in_stock' => $product->in_stock,
            'is_active' => $product->is_active,
            'url' => $product->url(),
            'angazny' => $product->isFromAngazny() ? [
                'price' => $product->angazny_price,
                'profit' => $product->angaznyProfit(),
                'depot' => $product->angazny_depot,
                'needs_price' => $product->angazny_needs_price,
            ] : null,
        ];
    }
}
