<?php

namespace Modules\Shop\Services;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Modules\Shop\Models\ShopProduct;

/**
 * The cart lives in the session and holds only product ids and choices.
 * Prices always come from the database, so a customer can never change them.
 */
class Cart
{
    private const SESSION_KEY = 'shop.cart';

    public function __construct(private readonly Session $session) {}

    public function add(ShopProduct $product, int $quantity, ?string $size, ?string $color): void
    {
        $key = md5($product->id.'|'.$size.'|'.$color);
        $entries = $this->entries();
        $current = $entries[$key]['quantity'] ?? 0;

        $entries[$key] = [
            'product_id' => $product->id,
            'size' => $size,
            'color' => $color,
            'quantity' => $this->limit($current + $quantity),
        ];

        $this->session->put(self::SESSION_KEY, $entries);
    }

    public function setQuantity(string $key, int $quantity): void
    {
        $entries = $this->entries();
        if (! isset($entries[$key])) {
            return;
        }

        $entries[$key]['quantity'] = $this->limit($quantity);
        $this->session->put(self::SESSION_KEY, $entries);
    }

    public function remove(string $key): void
    {
        $this->session->put(self::SESSION_KEY, array_diff_key($this->entries(), [$key => true]));
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) array_sum(array_column($this->entries(), 'quantity'));
    }

    /** Lines ready to show or order. Products that were hidden or sold out are left out. */
    public function lines(): Collection
    {
        $entries = collect($this->entries());
        $products = ShopProduct::whereIn('id', $entries->pluck('product_id'))->get()->keyBy('id');

        return $entries
            ->filter(fn (array $entry) => $products->get($entry['product_id'])?->canBeOrdered())
            ->map(fn (array $entry, string $key) => $this->line($key, $entry, $products->get($entry['product_id'])))
            ->values();
    }

    public function subtotal(Collection $lines): float
    {
        return round((float) $lines->sum('line_total'), 2);
    }

    private function line(string $key, array $entry, ShopProduct $product): array
    {
        return [
            'key' => $key,
            'product' => $product,
            'size' => $entry['size'],
            'color' => $entry['color'],
            'quantity' => $entry['quantity'],
            'unit_price' => (float) $product->price,
            'line_total' => round((float) $product->price * $entry['quantity'], 2),
        ];
    }

    private function entries(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    private function limit(int $quantity): int
    {
        return max(1, min($quantity, (int) config('shop.max_quantity')));
    }
}
