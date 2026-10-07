<?php

namespace Tests\Feature\Concerns;

use App\Models\CurrenciesExchange;

/**
 * Seeds real USD <-> EGP rows in currencies_exchanges for today, so money code
 * that converts amounts (wallet ledger, business_amount) has a rate to use.
 */
trait SeedsUsdEgpRates
{
    protected const USD = 1;

    protected const EGP = 2;

    protected const USD_TO_EGP = 50.0;

    protected function seedUsdEgpRates(): void
    {
        CurrenciesExchange::flushCache();
        $today = date('Y-m-d');

        CurrenciesExchange::create(['currency1' => self::USD, 'currency2' => self::EGP, 'rate' => self::USD_TO_EGP, 'date_string' => $today]);
        CurrenciesExchange::create(['currency1' => self::EGP, 'currency2' => self::USD, 'rate' => 1 / self::USD_TO_EGP, 'date_string' => $today]);
    }
}
