<?php

namespace Modules\Shop\Services\Content;

interface ProductImageMatcher
{
    /**
     * @param  list<string>  $colors
     * @param  array<int, array{media_type: string, data: string}>  $images  keyed by image number, starting at 1
     * @return array{color_images: list<array{color: string, image: int}>, gallery_order: list<int>}
     *
     * @throws ProductCopyUnavailable
     */
    public function match(string $productName, array $colors, array $images): array;
}
