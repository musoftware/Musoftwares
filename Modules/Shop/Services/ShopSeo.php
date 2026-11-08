<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;

/** Builds the title, meta tags and structured data of every store page. */
class ShopSeo
{
    public function page(string $title, array $overrides = []): array
    {
        return $overrides + [
            'title' => $title.' | '.config('shop.name'),
            'description' => config('shop.tagline'),
            'canonical' => url()->current(),
            'robots' => 'index,follow,max-image-preview:large',
            'image' => null,
            'type' => 'website',
            'schemas' => [],
        ];
    }

    public function privatePage(string $title): array
    {
        return $this->page($title, ['robots' => 'noindex,nofollow']);
    }

    public function excerpt(?string $text): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text))), 155);
    }

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('shop.name'),
            'url' => route('shop.home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('shop.search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param  array<string, string>  $trail  page name => address */
    public function breadcrumbs(array $trail): array
    {
        $position = 0;
        $items = [];
        foreach ($trail as $name => $url) {
            $items[] = ['@type' => 'ListItem', 'position' => ++$position, 'name' => $name, 'item' => $url];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    public function collection(ShopCategory $category, Collection $products): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'url' => $category->url(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $products->values()->map(fn (ShopProduct $product, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $product->url(),
                    'name' => $product->name,
                ])->all(),
            ],
        ];
    }

    public function product(ShopProduct $product): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $this->excerpt($product->description) ?: $product->name,
            'image' => $product->imageUrls(),
            'sku' => 'SHOP-'.$product->id,
            'brand' => ['@type' => 'Brand', 'name' => config('shop.name')],
            'category' => $product->category?->name,
            'offers' => $this->offer($product),
        ]);
    }

    public function toJson(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }

    private function offer(ShopProduct $product): array
    {
        return [
            '@type' => 'Offer',
            'url' => $product->url(),
            'price' => number_format((float) $product->price, 2, '.', ''),
            'priceCurrency' => config('shop.currency'),
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => 'https://schema.org/'.($product->in_stock ? 'InStock' : 'OutOfStock'),
        ];
    }
}
