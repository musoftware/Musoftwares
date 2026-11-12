<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\LocalPrice;
use Tests\TestCase;

/** The chat page shows prices in the customer's own money, at the same rate the payment gateway converts with. */
class LocalPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $usd = Currency::create(['currency' => 'USD', 'symbol' => '$', 'string_format' => '%v $', 'country_codes' => ['US'], 'is_default' => true]);
        $egp = Currency::create(['currency' => 'EGP', 'symbol' => 'EGP', 'string_format' => '%v EGP', 'country_codes' => ['EG']]);
        CurrenciesExchange::create(['currency1' => $usd->id, 'currency2' => $egp->id, 'rate' => 50, 'date_string' => now()->toDateString()]);
        CurrenciesExchange::flushCache();
    }

    private function project(?string $country): BuilderProject
    {
        return BuilderProject::create(['ip' => '127.0.0.1', 'locale' => 'ar', 'currency' => 'USD', 'country' => $country]);
    }

    public function test_an_egyptian_customer_sees_pounds_at_the_sites_rate(): void
    {
        app()->setLocale('ar');

        $local = app(LocalPrice::class)->for($this->project('EG'));

        $this->assertSame(['from' => 'USD', 'currency' => 'EGP', 'name' => 'جنيه', 'rate' => 50.0], $local);
    }

    public function test_a_country_the_site_has_no_currency_for_keeps_the_original_price(): void
    {
        // never the site's default currency for an unknown country
        $this->assertNull(app(LocalPrice::class)->for($this->project('KW')));
        $this->assertNull(app(LocalPrice::class)->for($this->project('US')));
    }

    public function test_the_page_state_carries_the_local_price(): void
    {
        $project = $this->project('EG');

        $this->withSession(['builder.started' => [$project->uuid]])
            ->getJson('/ai/'.$project->uuid.'/state')
            ->assertOk()
            ->assertJsonPath('local_price.currency', 'EGP')
            ->assertJsonPath('local_price.rate', 50);
    }
}
