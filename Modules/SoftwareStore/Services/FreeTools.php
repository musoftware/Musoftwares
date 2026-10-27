<?php

namespace Modules\SoftwareStore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        'qr-code-generator' => ['ri-qr-scan-2-line', ['company-website-design', 'landing-page-design', 'social-media-management']],
        'email-signature-generator' => ['ri-mail-settings-line', ['logo-and-brand-identity', 'company-website-design', 'email-sender-pro']],
        'utm-link-builder' => ['ri-links-line', ['paid-ads-management', 'social-media-management', 'landing-page-design']],
        'meta-tag-generator' => ['ri-google-line', ['company-website-design', 'website-care-plans', 'online-store-development']],
        'break-even-calculator' => ['ri-scales-3-line', ['shop-manager-pro', 'restaurant-pos-system', 'supermarket-system']],
        'freelance-rate-calculator' => ['ri-time-line', ['custom-web-app-development', 'company-website-design', 'logo-and-brand-identity']],
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

    /** Adds one view to the public counter. Search engine bots are not counted. */
    public static function recordView(string $slug, ?string $userAgent): void
    {
        if ($userAgent === null || preg_match('~bot|crawl|spider|slurp|facebookexternalhit|preview|headless~i', $userAgent)) {
            return;
        }

        $now = now();
        if (DB::table('free_tool_stats')->where('slug', $slug)->increment('views', 1, ['updated_at' => $now]) === 0) {
            DB::table('free_tool_stats')->insertOrIgnore(['slug' => $slug, 'views' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        self::$views = null;
    }

    /** @return array<string, int> slug => views */
    public static function views(): array
    {
        return self::$views ??= DB::table('free_tool_stats')->pluck('views', 'slug')->map(fn ($views) => (int) $views)->all();
    }

    public static function viewsOf(string $slug): int
    {
        return self::views()[$slug] ?? 0;
    }

    /** @var array<string, int>|null per-request cache of the counters */
    private static ?array $views = null;

    /** @return Collection<int, SoftwareProduct> active related products, in the order listed above */
    public static function relatedProducts(string $slug): Collection
    {
        $slugs = self::TOOLS[$slug][1];

        return SoftwareProduct::where('is_active', true)->whereIn('slug', $slugs)->get()
            ->sortBy(fn (SoftwareProduct $product) => array_search($product->slug, $slugs, true))
            ->values();
    }
}
