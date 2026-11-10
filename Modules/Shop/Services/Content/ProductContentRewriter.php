<?php

namespace Modules\Shop\Services\Content;

use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Angazny\AngaznyProductMapper;

/**
 * Replaces supplier text with copy fit for shoppers. The supplier's text is kept beside it, so any
 * product can be restored. Addresses never change, and text that breaks a rule is never saved.
 */
class ProductContentRewriter
{
    private const MIN_DESCRIPTION_LENGTH = 40;

    private const MAX_NAME_LENGTH = 120;

    private const FORBIDDEN = '~https?://|www\.|drive\.google|\d{10,}|<[a-z/]~iu';

    private const CATEGORY_SAMPLE = 20;

    public function __construct(
        private readonly ProductCopyWriter $writer,
        private readonly AngaznyProductMapper $mapper,
    ) {}

    /** @return array{products: int, categories: int, failed: list<int>} */
    public function handle(int $limit, ?int $angaznyId = null): array
    {
        $report = ['products' => 0, 'categories' => 0, 'failed' => []];

        foreach ($this->pending($limit, $angaznyId) as $product) {
            $report = $this->rewriteProduct($product, $report);
        }

        return $angaznyId === null ? $this->describeCategories($report) : $report;
    }

    private function pending(int $limit, ?int $angaznyId)
    {
        return ShopProduct::with('category')
            ->whereNotNull('angazny_id')
            ->when($angaznyId, fn ($query) => $query->where('angazny_id', $angaznyId))
            ->when(! $angaznyId, fn ($query) => $query->whereNull('content_rewritten_at')->orderByDesc('is_active')->inRandomOrder())
            ->limit($limit)
            ->get();
    }

    private function rewriteProduct(ShopProduct $product, array $report): array
    {
        try {
            $copy = $this->writer->product($this->facts($product));
            $this->assertSafe($copy['name'].' '.$copy['description']);
            $this->assertProductShape($copy);
        } catch (ProductCopyUnavailable $exception) {
            Log::warning('Product copy was not saved', ['product_id' => $product->id, 'error' => $exception->getMessage()]);
            $report['failed'][] = (int) $product->angazny_id;

            return $report;
        }

        $product->update($copy + [
            'original_name' => $product->original_name ?? $product->name,
            'original_description' => $product->original_description ?? $product->description,
            'content_rewritten_at' => now(),
        ]);
        $report['products']++;

        return $report;
    }

    private function facts(ShopProduct $product): array
    {
        return [
            'name' => $product->original_name ?? $product->name,
            'supplier_text' => (string) ($product->original_description ?? $product->description),
            'category' => $product->category?->name,
            'colors' => $product->colors ?? [],
            'sizes' => $product->sizes ?? [],
        ];
    }

    /** Only categories with no description are written, so an admin's own text is kept. */
    private function describeCategories(array $report): array
    {
        $categories = ShopCategory::visible()->withVisibleProducts()->whereNull('description')->with('products:id,category_id,name')->get();

        foreach ($categories as $category) {
            $report = $this->describeCategory($category, $report);
        }

        return $report;
    }

    private function describeCategory(ShopCategory $category, array $report): array
    {
        try {
            $names = $category->products->take(self::CATEGORY_SAMPLE)->pluck('name')->all();
            $description = $this->writer->category($category->name, $names);
            $this->assertSafe($description);
        } catch (ProductCopyUnavailable $exception) {
            Log::warning('Category copy was not saved', ['category_id' => $category->id, 'error' => $exception->getMessage()]);

            return $report;
        }

        $category->update(['description' => $description]);
        $report['categories']++;

        return $report;
    }

    private function assertSafe(string $text): void
    {
        if (preg_match(self::FORBIDDEN, $text) === 1 || $this->mapper->mentionsBrand($text)) {
            throw new ProductCopyUnavailable('Text contains a link, long number, HTML or brand name.');
        }
    }

    private function assertProductShape(array $copy): void
    {
        if ($copy['name'] === '' || mb_strlen($copy['name']) > self::MAX_NAME_LENGTH || mb_strlen($copy['description']) < self::MIN_DESCRIPTION_LENGTH) {
            throw new ProductCopyUnavailable('Name or description has the wrong length.');
        }
    }
}
