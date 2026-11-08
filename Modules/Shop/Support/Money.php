<?php

namespace Modules\Shop\Support;

final class Money
{
    public static function format(float|string $amount): string
    {
        $value = (float) $amount;
        $decimals = floor($value) === $value ? 0 : 2;

        return number_format($value, $decimals).' '.config('shop.currency_symbol');
    }
}
