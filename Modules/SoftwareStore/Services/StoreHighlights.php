<?php

namespace Modules\SoftwareStore\Services;

use Illuminate\Support\Collection;
use Modules\SoftwareStore\Models\SoftwareProduct;

/** What the homepage shows from the store: a few ready programs, as samples of our work. */
class StoreHighlights
{
    private const FEATURED = [
        'pharmacy-system', 'restaurant-pos-system', 'dental-clinic-system', 'shop-manager-pro', 'maps-leads-pro', 'company-website-design',
    ];

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
