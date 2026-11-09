<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shop\Models\ShopOrder;

class PlaceOrder
{
    public function __construct(private readonly Cart $cart) {}

    /** Saves the order and its items together: either all of it is stored or nothing is. */
    public function handle(array $customer, Collection $lines): ShopOrder
    {
        $subtotal = $this->cart->subtotal($lines);
        $shipping = $this->cart->shipping($lines);
        $angaznyStatus = $this->cart->hasAngaznyProduct($lines) ? ShopOrder::ANGAZNY_PENDING : null;

        $order = DB::transaction(function () use ($customer, $lines, $subtotal, $shipping, $angaznyStatus): ShopOrder {
            $order = ShopOrder::create($customer + [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
                'status' => 'new',
                'angazny_status' => $angaznyStatus,
            ]);
            $order->items()->createMany($lines->map(fn (array $line) => $this->itemData($line))->all());

            return $order;
        });

        $this->cart->clear();

        return $order;
    }

    private function itemData(array $line): array
    {
        return [
            'product_id' => $line['product']->id,
            'product_name' => $line['product']->name,
            'size' => $line['size'],
            'color' => $line['color'],
            'unit_price' => $line['unit_price'],
            'quantity' => $line['quantity'],
            'line_total' => $line['line_total'],
            'angazny_stock_id' => $line['angazny_stock_id'],
            'angazny_price_effect' => $line['angazny_price_effect'],
        ];
    }
}
