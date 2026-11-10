<?php

namespace Modules\Shop\Services;

use Carbon\Carbon;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;

/** Every store page worth indexing: the home page, categories that have products, and visible products. */
class ShopSitemap
{
    /** @return list<array{loc: string, lastmod: Carbon, images: list<string>}> */
    public function entries(): array
    {
        $products = ShopProduct::visible()->get(['id', 'slug', 'images', 'updated_at']);

        return [
            ['loc' => route('shop.home'), 'lastmod' => $products->max('updated_at') ?? now(), 'images' => []],
            ...$this->categoryEntries(),
            ...$products->map(fn (ShopProduct $product) => [
                'loc' => $product->url(),
                'lastmod' => $product->updated_at,
                'images' => $product->imageUrls(),
            ])->all(),
        ];
    }

    private function categoryEntries(): array
    {
        return ShopCategory::visible()
            ->withVisibleProducts()
            ->withMax(['products' => fn ($query) => $query->visible()], 'updated_at')
            ->get()
            ->map(fn (ShopCategory $category) => [
                'loc' => $category->url(),
                'lastmod' => max($category->updated_at, Carbon::parse($category->products_max_updated_at)),
                'images' => array_filter([$category->imageUrl()]),
            ])
            ->all();
    }
}
