<?php

namespace Modules\AppBuilder\Services;

use App\Models\ContractPriceItem;
use App\Services\ProjectEstimatorDataService;
use Illuminate\Support\Collection;

/**
 * What the site's public estimator says a new program costs: its customer pages or screens at the platform's rate,
 * plus each chosen module from the price list at its own price. An admin panel is one module with one price,
 * however many pages it has. The quote never goes below this, and neither does the price after delivery.
 */
class EstimatorPrice
{
    /**
     * @param  list<string>  $moduleKeys
     * @return array{platform: string, pages: int, page_rate: float, modules: list<array{key: string, name: string, price: float}>, total: float}|null
     */
    public function scope(string $projectType, int $pages, array $moduleKeys): ?array
    {
        $platform = $this->platformFor($projectType);
        if ($platform === null) {
            return null;
        }

        $rate = (float) ProjectEstimatorDataService::PAGE_RATE_USD[$platform];
        $modules = $this->items($platform)->whereIn('key', $moduleKeys)
            ->map(fn (ContractPriceItem $item) => ['key' => $item->key, 'name' => $this->name($item), 'price' => $item->unitPriceUsd()])
            ->values()->all();
        $pages = max(0, $pages);

        return [
            'platform' => $platform,
            'pages' => $pages,
            'page_rate' => $rate,
            'modules' => $modules,
            'total' => round($pages * $rate + array_sum(array_column($modules, 'price')), 2),
        ];
    }

    /** @return list<string> every module key the chat may choose */
    public function moduleKeys(): array
    {
        return $this->platforms()->flatMap(fn (string $platform) => $this->items($platform)->pluck('key'))->unique()->values()->all();
    }

    /** The modules per platform, for the chat's instructions. Prices stay out: the chat never says a price. */
    public function forPrompt(): string
    {
        return $this->platforms()
            ->map(fn (string $platform) => "- {$platform}: ".$this->items($platform)->map(fn (ContractPriceItem $item) => "`{$item->key}` ({$this->name($item)})")->implode(', '))
            ->implode("\n");
    }

    private function platformFor(string $projectType): ?string
    {
        return config("builder.estimator.platforms.{$projectType}");
    }

    /** @return Collection<int, string> */
    private function platforms(): Collection
    {
        return collect((array) config('builder.estimator.platforms'))->unique()->values();
    }

    /** @return Collection<int, ContractPriceItem> */
    private function items(string $platform): Collection
    {
        return once(fn () => ContractPriceItem::query()
            ->where('is_active', true)
            ->whereIn('platform', ContractPriceItem::PLATFORMS)
            ->whereNotNull('key')
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->groupBy('platform'))
            ->get($platform, collect());
    }

    private function name(ContractPriceItem $item): string
    {
        return (string) ($item->name_en ?: $item->name);
    }
}
