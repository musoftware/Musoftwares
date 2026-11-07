<?php

namespace Modules\Marketplace\Http\Requests\Concerns;

use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;

/**
 * A field the form does not send at all comes from the saved package, so an edit never wipes it.
 * A field sent empty stays empty, so the required rules can reject it. Only the package text and
 * currency have a default, because the seller form may not ask for them.
 */
trait FillsPackageDetails
{
    /** The seller form shows prices in US dollars. */
    private static string $sellerFormCurrency = 'USD';

    private static int $packageDescriptionLimit = 490;

    protected function fillPackageDetails(): void
    {
        $rows = $this->input('packages');
        if (! is_array($rows)) {
            return;
        }

        $saved = $this->savedPackages();
        $filled = array_map(fn ($row) => is_array($row) ? $this->fillPackageRow($row, $saved->get($row['id'] ?? null)) : $row, $rows);

        $this->merge(['packages' => $filled]);
    }

    /** @return Collection<int, ServicePackage> */
    private function savedPackages(): Collection
    {
        $service = $this->route('service');

        return $service instanceof Service ? $service->packages()->get()->keyBy('id') : collect();
    }

    /** @param array<string, mixed> $row */
    private function fillPackageRow(array $row, ?ServicePackage $saved): array
    {
        $base = $saved?->only(['name', 'description', 'price', 'old_price', 'currency_id', 'delivery_days', 'revisions', 'features']) ?? [];
        $package = [...$base, ...$row];

        $package['description'] = ($package['description'] ?? null) ?: $this->defaultPackageDescription($package);
        $package['currency_id'] = ($package['currency_id'] ?? null) ?: Currency::where('currency', self::$sellerFormCurrency)->value('id');

        return $package;
    }

    /** @param array<string, mixed> $package */
    private function defaultPackageDescription(array $package): ?string
    {
        $text = $this->input('tagline') ?: strip_tags((string) $this->input('description'));

        return Str::limit(trim((string) ($text ?: ($package['name'] ?? ''))), self::$packageDescriptionLimit) ?: null;
    }
}
