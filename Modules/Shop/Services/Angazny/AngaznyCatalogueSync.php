<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Support\UniqueSlug;

/**
 * Brings new Angazny products in hidden, and refreshes stock and cost on those already here.
 * Names, descriptions, photos and prices the admin edited are never overwritten.
 */
class AngaznyCatalogueSync
{
    private const PAUSE_MICROSECONDS = 500000;

    /** @var array<int, string> Angazny category id => name */
    private array $categories = [];

    public function __construct(
        private readonly AngaznyClient $client,
        private readonly AngaznyProductMapper $mapper,
        private readonly AngaznyImages $images,
    ) {}

    /** @return array{created: int, updated: int, sold_out: int, skipped: array<string, int>, failed: list<int>} */
    public function handle(): array
    {
        $this->categories = $this->client->categories();
        $report = ['created' => 0, 'updated' => 0, 'sold_out' => 0, 'skipped' => [], 'failed' => []];
        $ids = array_map('intval', array_column($this->client->products(), 'id'));

        foreach ($ids as $id) {
            $report = $this->syncOne($id, $report);
            usleep($this->pause());
        }

        $report['sold_out'] += $this->markMissingAsSoldOut($ids);

        return $report;
    }

    private function syncOne(int $id, array $report): array
    {
        try {
            $data = $this->client->product($id);
        } catch (RequestException|ConnectionException $exception) {
            Log::warning('Angazny product could not be read', ['angazny_id' => $id, 'error' => $exception->getMessage()]);
            $report['failed'][] = $id;

            return $report;
        }

        return $this->apply($id, $data, $report);
    }

    private function apply(int $id, array $data, array $report): array
    {
        $existing = ShopProduct::withTrashed()->where('angazny_id', $id)->first();
        $reason = $this->mapper->rejectionReason($data);

        if ($reason !== null) {
            return $this->reject($existing, $reason, $report);
        }

        if ($existing?->trashed()) {
            return $this->count($report, 'deleted_by_admin');
        }

        $existing ? $this->refresh($existing, $data) : $this->create($id, $data);
        $report[$existing ? 'updated' : 'created']++;

        return $report;
    }

    private function reject(?ShopProduct $existing, string $reason, array $report): array
    {
        if ($existing && ! $existing->trashed()) {
            $existing->update(['in_stock' => false]);
        }

        return $this->count($report, $reason);
    }

    private function count(array $report, string $reason): array
    {
        $report['skipped'][$reason] = ($report['skipped'][$reason] ?? 0) + 1;

        return $report;
    }

    private function create(int $id, array $data): void
    {
        $product = $data['product'];
        $name = $this->mapper->name($product);
        $live = $this->mapper->liveFields($data);
        $price = AngaznyPrice::forStore($live['angazny_price'], $live['angazny_commission']);

        ShopProduct::create($live + [
            'angazny_id' => $id,
            'category_id' => $this->categoryId((int) ($product['category_id'] ?? 0)),
            'name' => $name,
            'slug' => UniqueSlug::make(ShopProduct::class, $name),
            'description' => $this->mapper->description($product),
            'price' => $price ?? $live['angazny_price'],
            'angazny_needs_price' => $price === null,
            'images' => $this->images->copy($this->mapper->imageUrls($product)),
            'is_active' => false,
        ]);
    }

    /** A cost rise that puts the store under Angazny's price hides the product until it is repriced. */
    private function refresh(ShopProduct $product, array $data): void
    {
        $live = $this->mapper->liveFields($data);
        $belowCost = (float) $product->price < $live['angazny_price'];

        $product->update($live + [
            'angazny_needs_price' => $product->angazny_needs_price || $belowCost,
            'is_active' => $product->is_active && ! $belowCost,
        ]);
    }

    private function categoryId(int $angaznyCategoryId): ?int
    {
        $source = $this->categories[$angaznyCategoryId] ?? null;
        if ($source === null) {
            return null;
        }

        $name = config('shop.angazny.category_names')[$source] ?? $source;

        return ShopCategory::firstOrCreate(['name' => $name], ['slug' => UniqueSlug::make(ShopCategory::class, $name)])->id;
    }

    private function markMissingAsSoldOut(array $ids): int
    {
        return ShopProduct::whereNotNull('angazny_id')
            ->whereNotIn('angazny_id', $ids)
            ->where('in_stock', true)
            ->update(['in_stock' => false]);
    }

    private function pause(): int
    {
        return app()->runningUnitTests() ? 0 : self::PAUSE_MICROSECONDS;
    }
}
