<?php

namespace Modules\SoftwareStore\Services;

use App\Support\LocaleUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Everything a search engine or an AI assistant reads from a store page, built from the same
 * data as the visible page: structured data (JSON-LD), language alternates and a Markdown copy.
 */
class StoreSeo
{
    private const SCHEMA = 'https://schema.org';

    public function description(SoftwareProduct $product): string
    {
        $copy = $product->landingCopy();

        return Str::limit(strip_tags($copy['subheadline'] ?? $product->localized('tagline') ?? ''), 158, '');
    }

    /** Best picture for social cards: the first screenshot, then the icon. */
    public function ogImage(SoftwareProduct $product): string
    {
        $url = $product->galleryUrlsFor()[0] ?? $product->imageUrl();

        return $url ?: asset('images/apple/web-mobile-suite.jpg');
    }

    /** @return array<string, string> language => address */
    public function alternates(string $url): array
    {
        return LocaleUrl::alternates($url);
    }

    /** @return array<int, array<string, mixed>> */
    public function productGraph(SoftwareProduct $product): array
    {
        $url = $product->purchaseUrl();
        $graph = [$this->product($product, $url), $this->breadcrumb([[__('software.home'), url('/')], [__('software.title'), route('services.index')], [$product->localized('name'), $url]])];

        $faq = $this->faq($product);
        if ($faq) {
            $graph[] = $faq;
        }

        return $graph;
    }

    /** @param Collection<int, SoftwareProduct> $products */
    public function indexGraph(Collection $products): array
    {
        $url = route('services.index');

        return [
            [
                '@type' => 'CollectionPage',
                'name' => __('software.title'),
                'description' => __('software.meta_description'),
                'url' => $url,
                'inLanguage' => app()->getLocale(),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'itemListElement' => $products->values()->map(fn (SoftwareProduct $p, int $i) => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'url' => $p->purchaseUrl(),
                        'name' => $p->localized('name'),
                    ])->all(),
                ],
            ],
            $this->breadcrumb([[__('software.home'), url('/')], [__('software.title'), $url]]),
        ];
    }

    /** A free tool page: the tool as a free web app, its FAQ and the breadcrumb. */
    public function toolGraph(string $slug): array
    {
        $url = FreeTools::url($slug);
        $copy = __("free_tools.tools.{$slug}");

        return [
            [
                '@type' => 'WebApplication',
                'name' => $copy['name'],
                'description' => $copy['description'],
                'url' => $url,
                'inLanguage' => app()->getLocale(),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Any',
                'isAccessibleForFree' => true,
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EGP'],
                'publisher' => ['@type' => 'Organization', 'name' => 'Musoftware', 'url' => url('/')],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                ], $copy['faq']),
            ],
            $this->breadcrumb([[__('software.home'), url('/')], [__('software.title'), route('services.index')], [$copy['name'], $url]]),
        ];
    }

    /** One JSON-LD block that is safe to print inside a script tag. */
    public function toJson(array $graph): string
    {
        return json_encode(
            ['@context' => self::SCHEMA, '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }

    /** A plain Markdown copy of the page for AI assistants. */
    public function markdown(SoftwareProduct $product): string
    {
        $copy = $product->landingCopy();
        $lines = ['# '.$product->localized('name'), '', '> '.$product->localized('tagline'), '', $this->description($product), ''];
        $lines[] = '- '.__('software.md_page').': '.$product->purchaseUrl();
        $lines[] = '- '.__('software.md_price').': '.$this->priceText($product);

        foreach ($copy['overview']['items'] ?? [] as $item) {
            $lines[] = '- '.$item['title'].': '.$item['body'];
        }

        $lines = array_merge($lines, $this->markdownPlans($product), $this->markdownDetails($copy), $this->markdownFaq($copy));

        return implode("\n", $lines)."\n";
    }

    public function priceText(SoftwareProduct $product): string
    {
        $currency = $product->saleCurrency();
        $period = $product->periodLabel();
        $price = $product->hasPlans() ? __('software.from').' '.$product->money($product->startingPrice()) : $product->money($product->salePrice());

        $usd = $product->approxUsdLabel($product->hasPlans() ? $product->startingPrice() : $product->salePrice());

        return trim("{$price} {$currency} ".($usd ? "{$usd} " : '').$period);
    }

    /** "P1Y" for yearly products, null for one-time payments. */
    public function billingDuration(SoftwareProduct $product): ?string
    {
        $period = $product->landingCopy('en')['plans']['period'] ?? null;

        return $period ? null : 'P1Y';
    }

    private function product(SoftwareProduct $product, string $url): array
    {
        $isProgram = $product->serial_software_id || $product->releases()->exists();
        $images = array_values(array_filter([...$product->galleryUrlsFor(), $product->imageUrl()]));
        $data = [
            '@type' => $isProgram ? 'SoftwareApplication' : 'Service',
            '@id' => $url.'#product',
            'name' => $product->localized('name'),
            'description' => $this->description($product),
            'url' => $url,
            'inLanguage' => app()->getLocale(),
            'image' => $images,
            'brand' => ['@type' => 'Brand', 'name' => 'Musoftware'],
            'offers' => $this->offers($product, $url),
        ];

        if ($isProgram) {
            $data += $this->applicationFields($product);
        } else {
            $data['provider'] = ['@type' => 'Organization', 'name' => 'Musoftware', 'url' => url('/')];
            $data['serviceType'] = $product->localized('tagline');
        }

        $video = $this->video($product, $url);
        if ($video) {
            $data['subjectOf'] = $video;
        }

        return $data;
    }

    private function applicationFields(SoftwareProduct $product): array
    {
        $features = $product->landingCopy()['details']['list'] ?? [];
        $fields = [
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Windows 10, Windows 11, macOS',
            'screenshot' => $product->galleryUrlsFor(),
        ];

        if ($version = $product->latestRelease()?->version) {
            $fields['softwareVersion'] = $version;
        }
        if ($features) {
            $fields['featureList'] = $features;
        }

        return $fields;
    }

    private function offers(SoftwareProduct $product, string $url): array
    {
        $plans = $product->hasPlans() ? $product->planList() : [['name' => $product->localized('name'), 'price' => $product->salePrice()]];

        $offers = array_map(fn (array $plan) => $this->offer($product, $url, $plan), $plans);

        return count($offers) === 1 ? $offers[0] : [
            '@type' => 'AggregateOffer',
            'priceCurrency' => $product->saleCurrency(),
            'lowPrice' => $this->number($product->startingPrice()),
            'highPrice' => $this->number((float) max(array_column($plans, 'price'))),
            'offerCount' => count($offers),
            'offers' => $offers,
        ];
    }

    private function offer(SoftwareProduct $product, string $url, array $plan): array
    {
        $price = $this->number((float) $plan['price']);
        $offer = [
            '@type' => 'Offer',
            'name' => $plan['name'],
            'url' => $url,
            'price' => $price,
            'priceCurrency' => $product->saleCurrency(),
            'availability' => 'https://schema.org/InStock',
        ];

        if ($duration = $this->billingDuration($product)) {
            $offer['priceSpecification'] = [
                '@type' => 'UnitPriceSpecification',
                'price' => $price,
                'priceCurrency' => $product->saleCurrency(),
                'billingDuration' => $duration,
            ];
        }

        return $offer;
    }

    private function video(SoftwareProduct $product, string $url): ?array
    {
        $content = $product->videoFileUrl() ?: $product->embedUrl();
        if (! $content) {
            return null;
        }

        return array_filter([
            '@type' => 'VideoObject',
            'name' => $product->localized('name'),
            'description' => $this->description($product),
            'thumbnailUrl' => $product->posterUrl() ?: $this->ogImage($product),
            'uploadDate' => ($product->updated_at ?? now())->toIso8601String(),
            $product->videoFileUrl() ? 'contentUrl' : 'embedUrl' => $content,
        ]);
    }

    private function faq(SoftwareProduct $product): ?array
    {
        $items = $product->landingCopy()['faq']['items'] ?? [];
        if (! $items) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ], $items),
        ];
    }

    /** @param array<int, array{0: string, 1: string}> $crumbs */
    private function breadcrumb(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    private function number(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function markdownPlans(SoftwareProduct $product): array
    {
        if (! $product->hasPlans()) {
            return [];
        }

        $lines = ['', '## '.__('software.choose_plan')];
        foreach ($product->planList() as $plan) {
            $lines[] = "- **{$plan['name']}**: {$product->money((float) $plan['price'])} {$product->saleCurrency()} ".($product->approxUsdLabel((float) $plan['price']) ? $product->approxUsdLabel((float) $plan['price']).' ' : '').$product->periodLabel().(empty($plan['description']) ? '' : ' - '.$plan['description']);
            foreach ($plan['features'] as $feature) {
                $lines[] = "  - {$feature}";
            }
        }

        return $lines;
    }

    private function markdownDetails(array $copy): array
    {
        $details = $copy['details'] ?? [];
        if (! $details) {
            return [];
        }

        $lines = ['', '## '.($details['title'] ?? ''), ''];
        foreach ($details['paragraphs'] ?? [] as $paragraph) {
            $lines[] = $paragraph;
            $lines[] = '';
        }
        foreach ($details['list'] ?? [] as $item) {
            $lines[] = '- '.$item;
        }

        return $lines;
    }

    private function markdownFaq(array $copy): array
    {
        $faq = $copy['faq'] ?? [];
        if (empty($faq['items'])) {
            return [];
        }

        $lines = ['', '## '.($faq['title'] ?? '')];
        foreach ($faq['items'] as $item) {
            array_push($lines, '', '**'.$item['q'].'**', $item['a']);
        }

        return $lines;
    }
}
