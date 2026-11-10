<?php

namespace Modules\Shop\Services\Angazny;

use Illuminate\Support\Collection;

/** Turns one Angazny product into store fields, or says why it can't be carried. Calls nothing outside. */
final class AngaznyProductMapper
{
    public const WRONG_WAREHOUSE = 'other_warehouse';

    public const BRANDED = 'brand';

    public const UNSUPPORTED_OPTIONS = 'unsupported_options';

    public const UNCLEAR_OPTIONS = 'unclear_options';

    private const OPTION_KINDS = [
        'اللون' => 'color', 'الالوان' => 'color', 'الألوان' => 'color', 'لون' => 'color', 'color' => 'color',
        'المقاس' => 'size', 'مقاس' => 'size', 'المقاسات' => 'size', 'size' => 'size', 'siez' => 'size',
        'default' => 'default',
    ];

    private const CHECKS = [
        self::WRONG_WAREHOUSE => 'isFromAnotherWarehouse',
        self::BRANDED => 'isBranded',
        self::UNSUPPORTED_OPTIONS => 'hasUnsupportedOptions',
        self::UNCLEAR_OPTIONS => 'hasUnclearOptions',
    ];

    public function mentionsBrand(string $text): bool
    {
        $words = implode('|', array_map(fn (string $word) => preg_quote($word, '/'), config('shop.angazny.brands')));

        return preg_match('/(?<![\p{L}\p{N}])('.$words.')(?![\p{L}\p{N}])/iu', $text) === 1;
    }

    /** @param  array<string, mixed>  $data  the product view response */
    public function rejectionReason(array $data): ?string
    {
        foreach (self::CHECKS as $reason => $check) {
            if ($this->{$check}($data)) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * Fields Angazny owns, safe to refresh on every sync.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function liveFields(array $data): array
    {
        $variants = $this->variants($data);
        $available = $variants->filter(fn (array $variant) => $variant['stock'] > 0);

        return [
            'angazny_price' => (float) ($data['product']['price'] ?? 0),
            'angazny_commission' => (float) ($data['product']['net_commission'] ?? 0),
            'angazny_depot' => $data['product']['depot']['name'] ?? null,
            'angazny_variants' => $variants->all(),
            'sizes' => $this->values($available, 'size'),
            'colors' => $this->values($available, 'color'),
            'in_stock' => $available->isNotEmpty(),
        ];
    }

    /** Removes stretching marks and extra spaces: "ســوت  نسائي" becomes "سوت نسائي". */
    public function name(array $product): string
    {
        return $this->tidy((string) ($product['Name_AR'] ?? $product['Name'] ?? ''));
    }

    /** Plain lines with no supplier markup or links. */
    public function description(array $product): ?string
    {
        $html = preg_replace('~<br\s*/?>|</p>|</div>~i', "\n", (string) ($product['Description'] ?? '')) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = array_filter(array_map(
            fn (string $line) => $this->tidy((string) preg_replace('~https?://\S+~i', '', $line)),
            explode("\n", $text),
        ), fn (string $line) => $line !== '');

        return $lines === [] ? null : implode("\n", $lines);
    }

    /** @return list<string> the main image first */
    public function imageUrls(array $product): array
    {
        $urls = [$product['image'] ?? null, ...($product['image_gallery'] ?? [])];

        return array_values(array_unique(array_filter($urls, 'is_string')));
    }

    private function isFromAnotherWarehouse(array $data): bool
    {
        return (int) ($data['product']['depot']['id'] ?? 0) !== (int) config('shop.angazny.depot_id');
    }

    private function isBranded(array $data): bool
    {
        $product = $data['product'] ?? [];

        if (in_array((int) ($product['id'] ?? 0), config('shop.angazny.blocked_ids'), true)) {
            return true;
        }

        return $this->mentionsBrand(strip_tags(implode(' ', array_map(
            fn (string $field) => (string) ($product[$field] ?? ''),
            ['Name', 'Name_AR', 'Description', 'Description_ad'],
        ))));
    }

    private function hasUnsupportedOptions(array $data): bool
    {
        return collect($data['options'] ?? [])
            ->contains(fn (array $option) => $this->kind($option) === null);
    }

    /** Two choices with one name, or a stock id no choice leads to, could ship the wrong item. */
    private function hasUnclearOptions(array $data): bool
    {
        $labels = $this->labels($data);
        $values = array_map(fn (array $label) => $label['kind'].':'.$label['value'], $labels);
        $paths = collect($data['stocks_id_by_path'] ?? []);

        return count($values) !== count(array_unique($values))
            || $paths->isEmpty()
            || $paths->contains(fn (array $stock) => array_diff($stock['path'], array_keys($labels)) !== []);
    }

    private function kind(array $option): ?string
    {
        $name = mb_strtolower($this->tidy((string) ($option['optionName'] ?? '')));

        return self::OPTION_KINDS[$name] ?? null;
    }

    /** @return array<int, array{kind: string|null, value: string}> keyed by Angazny's value id */
    private function labels(array $data): array
    {
        return collect($data['options'] ?? [])
            ->flatMap(fn (array $option) => array_map(fn (array $value) => [
                'id' => (int) $value['id'],
                'kind' => $this->kind($option),
                'value' => $this->optionValue((string) $value['value']),
            ], $option['values'] ?? []))
            ->keyBy('id')
            ->all();
    }

    private function variants(array $data): Collection
    {
        $labels = $this->labels($data);
        $stock = collect($data['stocks_quantity_by_path'] ?? [])
            ->mapWithKeys(fn (array $row) => [$this->pathKey($row['path']) => (int) ($row['stock'] ?? 0)]);

        return collect($data['stocks_id_by_path'] ?? [])->map(function (array $row) use ($labels, $stock): array {
            $picked = collect($row['path'])->map(fn (int $id) => $labels[$id])->pluck('value', 'kind');

            return [
                'size' => $picked->get('size'),
                'color' => $picked->get('color'),
                'stock_id' => (int) $row['id'],
                'stock' => (int) $stock->get($this->pathKey($row['path']), 0),
            ];
        })->values();
    }

    private function values(Collection $variants, string $field): array
    {
        return $variants->pluck($field)->filter()->unique()->values()->all();
    }

    private function pathKey(array $path): string
    {
        sort($path);

        return implode('-', $path);
    }

    /** Angazny writes some sizes as ".M". A value that is only dots stays as it is. */
    private function optionValue(string $value): string
    {
        $tidy = $this->tidy($value);

        return trim($tidy, '. ') ?: $tidy;
    }

    private function tidy(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', str_replace('ـ', '', $text)));
    }
}
