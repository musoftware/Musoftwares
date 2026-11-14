<?php

namespace Modules\Shop\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The filters of a product list: size, colour and price. The choices offered come from the products of the list
 * itself, so a filter never leads to an empty page by design.
 */
class ProductFilters
{
    /** @var list<string> */
    public readonly array $sizes;

    /** @var list<string> */
    public readonly array $colors;

    public readonly ?string $price;

    public function __construct(Request $request)
    {
        $this->sizes = $this->chosen($request, 'size');
        $this->colors = $this->chosen($request, 'color');
        $price = (string) $request->query('price');
        $this->price = preg_match('/^\d{1,7}-\d{0,7}$/', $price) === 1 ? $price : null;
    }

    public function count(): int
    {
        return count($this->sizes) + count($this->colors) + (int) ($this->price !== null);
    }

    /**
     * Any chosen size and any chosen colour, inside the chosen price range. Sizes and colours are matched here, not
     * in the database: the server's JSON search misses Arabic values, which are saved escaped (ل...).
     */
    public function apply(Builder $products): Builder
    {
        if ($this->sizes !== [] || $this->colors !== []) {
            $matching = (clone $products)->get(['shop_products.id', 'sizes', 'colors'])
                ->filter(fn ($product) => $this->hasAny($product->sizes, $this->sizes) && $this->hasAny($product->colors, $this->colors));
            $products->whereKey($matching->modelKeys());
        }
        if ($this->price === null) {
            return $products;
        }
        [$from, $to] = explode('-', $this->price);

        return $products->where('price', '>=', (int) $from)->when($to !== '', fn (Builder $query) => $query->where('price', '<', (int) $to));
    }

    /**
     * What the list can be filtered by, read from its own products before any filter.
     *
     * @return array{sizes: list<string>, colors: list<string>, prices: list<array{key: string, label: string}>}
     */
    public function choices(Builder $products): array
    {
        $rows = $products->get(['sizes', 'colors', 'price']);

        return [
            'sizes' => $this->values($rows, 'sizes'),
            'colors' => $this->values($rows, 'colors'),
            'prices' => $this->priceRanges($rows->pluck('price')->map(fn ($price) => (float) $price)),
        ];
    }

    /** @return list<string> */
    private function chosen(Request $request, string $name): array
    {
        $values = array_filter((array) $request->query($name), fn ($value) => is_string($value) && $value !== '' && mb_strlen($value) <= 40);

        return array_values(array_unique(array_slice($values, 0, 20)));
    }

    /** Nothing chosen lets every product through; otherwise the product must have one of the chosen values. */
    private function hasAny(?array $values, array $chosen): bool
    {
        return $chosen === [] || array_intersect(array_map('strval', $values ?? []), $chosen) !== [];
    }

    /** @return list<string> every size or colour the products have, numbers in order first */
    private function values(Collection $rows, string $column): array
    {
        return $rows->pluck($column)->flatten()->filter()->map(fn ($value) => (string) $value)->unique()->sort(SORT_NATURAL)->values()->all();
    }

    /**
     * @param  Collection<int, float>  $prices
     * @return list<array{key: string, label: string}> only the ranges that hold a product, and none when all are in one
     */
    private function priceRanges(Collection $prices): array
    {
        $edges = [0, ...config('shop.filters.price_edges'), null];
        $ranges = collect(array_slice($edges, 0, -1))
            ->map(fn (int $from, int $index) => [$from, $edges[$index + 1]])
            ->filter(fn (array $range) => $prices->contains(fn (float $price) => $price >= $range[0] && ($range[1] === null || $price < $range[1])))
            ->map(fn (array $range) => ['key' => $range[0].'-'.$range[1], 'label' => $this->rangeLabel(...$range)])
            ->values();

        return $ranges->count() > 1 ? $ranges->all() : [];
    }

    private function rangeLabel(int $from, ?int $to): string
    {
        $currency = config('shop.currency_symbol');

        return match (true) {
            $from === 0 => 'أقل من '.number_format($to).' '.$currency,
            $to === null => 'أكثر من '.number_format($from).' '.$currency,
            default => number_format($from).' - '.number_format($to).' '.$currency,
        };
    }
}
