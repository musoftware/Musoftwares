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

    /** @return Collection<int, SoftwareProduct> */
    public function featured(): Collection
    {
        $visible = $this->visible()->keyBy('slug');

        return collect(self::FEATURED)->map(fn (string $slug) => $visible->get($slug))->filter()->values();
    }

    /** @return Collection<int, SoftwareProduct> */
    private function visible(): Collection
    {
        return SoftwareProduct::where('is_active', true)->get();
    }
}
