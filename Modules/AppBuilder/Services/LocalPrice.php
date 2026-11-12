<?php

namespace Modules\AppBuilder\Services;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Throwable;

/**
 * Prices in the customer's own money. The project keeps its price in its own currency (USD), and the gateway
 * converts it with the site's rates at payment; the page shows the same conversion, so an Egyptian customer
 * reads the pounds they will really pay. A country with no rate on the site keeps the original price.
 */
class LocalPrice
{
    /** @return array{from: string, currency: string, name: string, rate: float}|null null when the price is already local or no rate exists */
    public function for(BuilderProject $project): ?array
    {
        $local = $this->currencyOf($this->countryOf($project));
        if ($local === null || $local === $project->currency) {
            return null;
        }

        $rate = $this->rate((string) $project->currency, $local);

        return $rate === null ? null : [
            'from' => (string) $project->currency,
            'currency' => $local,
            'name' => Lang::has('builder.currency_names.'.$local) ? __('builder.currency_names.'.$local) : $local,
            'rate' => $rate,
        ];
    }

    /** The country saved when the chat opened; older chats only kept the customer's IP. */
    private function countryOf(BuilderProject $project): ?string
    {
        if ($project->country) {
            return strtoupper($project->country);
        }
        if (! $project->ip) {
            return null;
        }

        $country = Cache::remember('builder_ip_country_'.md5($project->ip), now()->addDay(), fn () => (string) app(IpGeolocationService::class)->getCountryFromIp($project->ip));

        return $country !== '' ? strtoupper($country) : null;
    }

    /** Only a currency the site really has for this country, never the site's default for an unknown one. */
    private function currencyOf(?string $country): ?string
    {
        if ($country === null) {
            return null;
        }

        return Currency::all()->first(fn (Currency $currency) => in_array($country, array_map('strtoupper', (array) ($currency->country_codes ?? [])), true))?->currency;
    }

    /** Today's rate from the site's own table, the one the gateway converts with. */
    private function rate(string $from, string $to): ?float
    {
        $ids = Currency::whereIn('currency', [$from, $to])->pluck('id', 'currency');
        if (! isset($ids[$from], $ids[$to])) {
            return null;
        }

        try {
            $rate = (float) CurrenciesExchange::RateTodayNoRound(1, $ids[$from], $ids[$to]);
        } catch (Throwable $error) {
            Log::warning('Builder local price: no rate', ['from' => $from, 'to' => $to, 'error' => $error->getMessage()]);

            return null;
        }

        return $rate > 0 ? $rate : null;
    }
}
