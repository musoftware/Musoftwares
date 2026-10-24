<?php

namespace Modules\SoftwareStore\Services;

use Illuminate\Support\Collection;
use Modules\SoftwareStore\Models\SoftwareProduct;

/** What the homepage shows about the store: how many products per group, and a few to feature. */
class StoreHighlights
{
    private const FEATURED = [
        'pharmacy-system', 'restaurant-pos-system', 'dental-clinic-system', 'shop-manager-pro', 'maps-leads-pro', 'company-website-design',
    ];

    /** @return array<string, int> group key => number of visible products */
    public function groupCounts(): array
    {
        $counts = $this->visible()->countBy(fn (SoftwareProduct $product) => $product->group())->all();

        return ['programs' => $counts['programs'] ?? 0, 'systems' => $counts['systems'] ?? 0, 'services' => $counts['services'] ?? 0];
    }

    /** @return array<string, float|null> group key => lowest starting price in EGP, null when the group has no EGP product */
    public function groupFromPrices(): array
    {
        $egp = $this->visible()->filter(fn (SoftwareProduct $product) => $product->saleCurrency() === 'EGP');

        return collect(['programs', 'systems', 'services'])
            ->mapWithKeys(fn (string $group) => [$group => $egp->filter(fn (SoftwareProduct $product) => $product->group() === $group)->min(fn (SoftwareProduct $product) => $product->startingPrice())])
            ->all();
    }

    /** @return Collection<int, SoftwareProduct> */
    public function featured(): Collection
    {
        $visible = $this->visible()->keyBy('slug');

        return collect(self::FEATURED)->map(fn (string $slug) => $visible->get($slug))->filter()->values();
    }

    /**
     * Real screenshots for the homepage hero: the first picture of the first featured products that have one.
     *
     * @return Collection<int, array{product: SoftwareProduct, image: string}>
     */
    public function heroShots(int $count = 3): Collection
    {
        return $this->featured()
            ->map(fn (SoftwareProduct $product) => ['product' => $product, 'image' => $product->galleryUrlsFor()[0] ?? null])
            ->filter(fn (array $shot) => $shot['image'])
            ->take($count)
            ->values();
    }

    /** @return Collection<int, SoftwareProduct> */
    private function visible(): Collection
    {
        return SoftwareProduct::where('is_active', true)->get();
    }
}
