<?php

namespace Modules\SoftwareStore\Services;

use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalog;
use Modules\SoftwareStore\Database\Seeders\SystemsCopyBaseline;
use Modules\SoftwareStore\Models\SoftwareProduct;

/**
 * Pushes the corrected store copy of the first 15 ready-made systems to products that already exist.
 * The seeder only adds missing text, so this replaces a text block only while it still equals a copy
 * that was seeded before (by fingerprint). A block the admin edited by hand is kept and reported.
 * Only the taglines, descriptions and landing text change: never prices, plans, visibility, media or sections.
 */
class SystemsCopyRefresh
{
    public const UPDATE = 'update';

    public const ADD = 'add';

    public const KEEP = 'kept (edited in the admin)';

    private const FIELDS = ['tagline_en', 'tagline_ar', 'description_en', 'description_ar'];

    /** @var array<string, array<string, array<int, string>>> slug => key => fingerprints of earlier seeded copy */
    private readonly array $baseline;

    public function __construct(?array $baseline = null)
    {
        $this->baseline = $baseline ?? SystemsCopyBaseline::fingerprints();
    }

    /**
     * What would change, per product: key ("tagline_en" or "lang.block") => action. Blocks that are already current are left out.
     *
     * @return array<string, array<string, string>>
     */
    public function plan(): array
    {
        $plan = [];
        foreach ($this->products() as $slug => [$product, $seeded]) {
            $plan[$slug] = array_filter(array_map(
                fn (string $key) => $this->action($slug, $key, $this->stored($product, $key), $this->value($seeded, $key)),
                array_combine($this->keys($seeded), $this->keys($seeded)),
            ));
        }

        return array_filter($plan);
    }

    /**
     * Writes the planned updates and additions. Returns the same plan that was applied.
     *
     * @return array<string, array<string, string>>
     */
    public function apply(): array
    {
        $plan = $this->plan();
        foreach ($this->products() as $slug => [$product, $seeded]) {
            $writable = array_keys(array_filter($plan[$slug] ?? [], fn (string $action) => $action !== self::KEEP));
            if ($writable !== []) {
                $this->write($product, $seeded, $writable);
            }
        }

        return $plan;
    }

    /** @return array<string, array{0: SoftwareProduct, 1: array<string, mixed>}> */
    private function products(): array
    {
        $seeded = collect(SystemsCatalog::programs())->keyBy('slug')->only(array_keys($this->baseline));
        $products = SoftwareProduct::whereIn('slug', $seeded->keys())->get()->keyBy('slug');

        return $products->map(fn (SoftwareProduct $product, string $slug) => [$product, $this->seededCopy($seeded[$slug])])->all();
    }

    /** @return array<string, mixed> */
    private function seededCopy(array $program): array
    {
        return array_intersect_key($program, array_flip(self::FIELDS)) + ['landing' => SoftwareProductSeeder::withExtras($program['slug'], $program['landing'])];
    }

    /** @return array<int, string> */
    private function keys(array $seeded): array
    {
        $landing = array_merge(...array_map(fn (string $lang) => array_map(fn ($key) => "{$lang}.{$key}", array_keys($seeded['landing'][$lang])), ['ar', 'en']));

        return array_merge(self::FIELDS, $landing);
    }

    private function action(string $slug, string $key, mixed $stored, mixed $new): ?string
    {
        if ($stored === null) {
            return self::ADD;
        }
        $fingerprint = SystemsCopyBaseline::fingerprint($stored);
        if ($fingerprint === SystemsCopyBaseline::fingerprint($new)) {
            return null;
        }
        $known = $this->baseline[$slug][$key] ?? [];

        return in_array($fingerprint, $known, true) ? self::UPDATE : self::KEEP;
    }

    private function stored(SoftwareProduct $product, string $key): mixed
    {
        return $this->value(['landing' => $product->landing ?? []] + $product->only(self::FIELDS), $key);
    }

    private function value(array $copy, string $key): mixed
    {
        if (in_array($key, self::FIELDS, true)) {
            return $copy[$key] ?? null;
        }
        [$lang, $block] = explode('.', $key, 2);

        return $copy['landing'][$lang][$block] ?? null;
    }

    /** @param  array<int, string>  $keys */
    private function write(SoftwareProduct $product, array $seeded, array $keys): void
    {
        $landing = $product->landing ?? [];
        foreach ($keys as $key) {
            if (in_array($key, self::FIELDS, true)) {
                $product->{$key} = $seeded[$key];

                continue;
            }
            [$lang, $block] = explode('.', $key, 2);
            $landing[$lang][$block] = $seeded['landing'][$lang][$block];
        }
        $product->landing = $landing;
        $product->save();
    }
}
