<?php

namespace Modules\Shop\Services\Content;

interface ProductCopyWriter
{
    /**
     * @param  array{name: string, supplier_text: string, category: ?string, colors: list<string>, sizes: list<string>}  $facts
     * @return array{name: string, description: string}
     *
     * @throws ProductCopyUnavailable
     */
    public function product(array $facts): array;

    /**
     * @param  list<string>  $productNames
     *
     * @throws ProductCopyUnavailable
     */
    public function category(string $name, array $productNames): string;
}
