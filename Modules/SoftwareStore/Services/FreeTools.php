<?php

namespace Modules\SoftwareStore\Services;

use Illuminate\Support\Collection;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * The free tools listed in /services. Each one runs in the visitor's browser (nothing is fetched
 * from other sites) and points to the paid products it is closest to. Copy lives in lang/{en,ar}/free_tools.php.
 */
class FreeTools
{
    /** slug => [remix icon, related store product slugs] */
    public const TOOLS = [
        'whatsapp-link-generator' => ['ri-whatsapp-line', ['whatsapp-bulk-sender', 'ai-chatbot-whatsapp-automation', 'whatsapp-official']],
        'phone-number-cleaner' => ['ri-contacts-book-line', ['whatsapp-bulk-sender', 'maps-leads-pro', 'telegram-sender-pro']],
        'invoice-generator' => ['ri-file-list-3-line', ['shop-manager-pro', 'supermarket-system', 'wholesale-system']],
        'qr-menu-maker' => ['ri-qr-code-line', ['restaurant-pos-system', 'coffee-shop-system', 'company-website-design']],
        'vat-calculator' => ['ri-percent-line', ['shop-manager-pro', 'wholesale-system', 'pharmacy-system']],
        'salary-tax-calculator' => ['ri-money-dollar-circle-line', ['clinic-system', 'workshop-system', 'education-center-system']],
        'profit-margin-calculator' => ['ri-line-chart-line', ['shop-manager-pro', 'supermarket-system', 'paid-ads-management']],
    ];

    public static function exists(string $slug): bool
    {
        return isset(self::TOOLS[$slug]);
    }

    /** @return array<int, string> */
    public static function slugs(): array
    {
        return array_keys(self::TOOLS);
    }

    public static function icon(string $slug): string
    {
        return self::TOOLS[$slug][0];
    }

    public static function url(string $slug): string
    {
        return route('services.tools.show', $slug);
    }

    /** @return Collection<int, SoftwareProduct> active related products, in the order listed above */
    public static function relatedProducts(string $slug): Collection
    {
        $slugs = self::TOOLS[$slug][1];

        return SoftwareProduct::where('is_active', true)->whereIn('slug', $slugs)->get()
            ->sortBy(fn (SoftwareProduct $product) => array_search($product->slug, $slugs, true))
            ->values();
    }
}
