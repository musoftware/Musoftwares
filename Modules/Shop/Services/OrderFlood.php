<?php

namespace Modules\Shop\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Shop\Models\ShopOrder;

/**
 * Keeps one phone number from flooding the store with orders, and clears a flood that got in. A real customer
 * makes a few orders a day at most; a script repeats the same order many times in the same second.
 */
class OrderFlood
{
    /** One phone places one order at a time: a repeat sent in the same moment finds the door closed. */
    private const SECONDS_BETWEEN_ORDERS = 15;

    /** May this phone place an order now? Asking takes the phone's turn, so the same answer is never given twice at once. */
    public function allows(string $phone): bool
    {
        if (! Cache::lock('shop-order:'.$phone, self::SECONDS_BETWEEN_ORDERS)->get()) {
            return false;
        }

        return $this->newOrders()->where('phone', $phone)->where('created_at', '>=', now()->subDay())->count() < $this->limit();
    }

    /** How many orders the clear button would remove. */
    public function count(): int
    {
        return $this->flood()->count();
    }

    /** Removes the flood from the list. The orders are kept in the database, so a mistake can be put back. */
    public function clear(): int
    {
        return $this->flood()->delete();
    }

    /** The new orders of every phone that made more of them in one day than a real customer does. */
    private function flood(): Builder
    {
        $phones = $this->newOrders()->select('phone')->groupByRaw('phone, date(created_at)')->havingRaw('count(*) > ?', [$this->limit()])->pluck('phone')->unique();

        return $this->newOrders()->whereIn('phone', $phones);
    }

    private function newOrders(): Builder
    {
        return ShopOrder::query()->where('status', 'new');
    }

    private function limit(): int
    {
        return (int) config('shop.orders.new_per_phone_a_day');
    }
}
