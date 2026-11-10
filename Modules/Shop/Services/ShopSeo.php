<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Support\Money;

/** Builds the title, meta tags, social cards and structured data of every store page. */
class ShopSeo
{
    public const INDEXABLE = 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';

    private const IMAGE_DISK = 'public_uploads';

    private const META_LENGTH = 155;

    private const SCHEMA_DESCRIPTION_LENGTH = 5000;

    public function page(string $title, array $overrides = []): array
    {
        return $overrides + [
            'title' => $title.' | '.config('shop.name'),
            'description' => config('shop.tagline'),
            'canonical' => url()->current(),
            'robots' => self::INDEXABLE,
            'image' => null,
            'image_alt' => null,
            'image_meta' => [],
            'type' => 'website',
            'og_tags' => [],
            'twitter_labels' => [],
            'schemas' => [],
        ];
    }

    public function privatePage(string $title): array
    {
        return $this->page($title, ['robots' => 'noindex,nofollow']);
    }

    public function excerpt(?string $text): string
    {
        return Str::limit($this->plainText($text), self::META_LENGTH);
    }

    /** The picture shown when a page is shared, with its real size so previews appear on the first share. */
    public function shareImage(?string $path, string $alt): array
    {
        if ($path === null) {
            return [];
        }

        return ['image' => asset('uploads/'.$path), 'image_alt' => $alt, 'image_meta' => $this->imageMeta($path)];
    }

    public function homeDescription(Collection $categories): string
    {
        $names = $categories->take(4)->pluck('name')->implode('، ');
        $offer = config('shop.name').': '.config('shop.tagline').'. '.config('shop.announcement').'.';

        return $this->excerpt($names === '' ? $offer : $offer.' تسوق '.$names.'.');
    }

    public function productDescription(ShopProduct $product): string
    {
        $details = preg_replace('/^وصف المنتج\s*/u', '', $this->plainText($product->description));

        return $this->excerpt($product->name.' بسعر '.Money::format($product->price).'، الدفع عند الاستلام. '.$details);
    }

    /** Price and stock details read by Facebook, WhatsApp, Pinterest and X. */
    public function productCard(ShopProduct $product): array
    {
        $price = number_format((float) $product->price, 2, '.', '');
        $currency = config('shop.currency');
        $inStock = $product->in_stock;

        return [
            'og_tags' => [
                'product:price:amount' => $price,
                'product:price:currency' => $currency,
                'product:availability' => $inStock ? 'in stock' : 'out of stock',
                'product:condition' => 'new',
                'product:retailer_item_id' => $this->sku($product),
                'og:price:amount' => $price,
                'og:price:currency' => $currency,
                'og:availability' => $inStock ? 'instock' : 'oos',
            ],
            'twitter_labels' => [
                'السعر' => Money::format($product->price),
                'التوفر' => $inStock ? 'متاح' : 'نفذت الكمية',
            ],
        ];
    }

    public function onlineStore(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'OnlineStore',
            'name' => config('shop.name'),
            'url' => route('shop.home'),
            'logo' => asset('favicon.png'),
            'description' => config('shop.tagline'),
            'currenciesAccepted' => config('shop.currency'),
            'paymentAccepted' => 'Cash on delivery',
            'areaServed' => ['@type' => 'Country', 'name' => 'Egypt'],
            'sameAs' => ['https://x.com/'.ltrim((string) config('shop.twitter_site'), '@')],
        ];
    }

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('shop.name'),
            'url' => route('shop.home'),
            'inLanguage' => 'ar-EG',
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

    public function itemList(Collection $products): array
    {
        return ['@context' => 'https://schema.org'] + $this->listOf($products);
    }

    public function collection(ShopCategory $category, Collection $products): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'url' => $category->url(),
            'mainEntity' => $this->listOf($products),
        ];
    }

    public function product(ShopProduct $product): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => Str::limit($this->plainText($product->description), self::SCHEMA_DESCRIPTION_LENGTH) ?: $product->name,
            'image' => $product->imageUrls(),
            'sku' => $this->sku($product),
            'category' => $product->category?->name,
            'color' => implode('، ', $product->colors ?? []),
            'size' => implode('، ', $product->sizes ?? []),
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
            'seller' => ['@type' => 'Organization', 'name' => config('shop.name')],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => $product->shippingCost(), 'currency' => config('shop.currency')],
                'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'EG'],
            ],
        ];
    }

    private function listOf(Collection $products): array
    {
        return [
            '@type' => 'ItemList',
            'itemListElement' => $products->values()->map(fn (ShopProduct $product, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $product->url(),
                'name' => $product->name,
            ])->all(),
        ];
    }

    private function sku(ShopProduct $product): string
    {
        return 'SHOP-'.$product->id;
    }

    private function plainText(?string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
    }

    /** @return array<string, int|string> */
    private function imageMeta(string $path): array
    {
        $file = Storage::disk(self::IMAGE_DISK)->path($path);
        $size = is_file($file) ? @getimagesize($file) : false;

        return $size === false ? [] : ['width' => $size[0], 'height' => $size[1], 'type' => $size['mime']];
    }
}
