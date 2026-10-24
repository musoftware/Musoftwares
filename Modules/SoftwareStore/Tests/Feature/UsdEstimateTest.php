<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\CurrenciesExchange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class UsdEstimateTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private function seedStore(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);
    }

    public function test_english_pages_show_an_estimated_usd_next_to_egp_prices_and_say_it_is_only_an_estimate(): void
    {
        $this->seedUsdEgpRates();
        $this->seedStore();

        $index = $this->get('/services?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('(about USD 100)', $index, '5,000 EGP at 50 EGP per USD');

        $page = $this->get('/services/pharmacy-system?lang=en')->assertOk();
        $page->assertSee('(about USD 100)', false)->assertSee('(about USD 400)', false);
        $page->assertSee('You are charged in Egyptian pounds (EGP). The USD amount is only an estimate', false);
    }

    public function test_arabic_pages_and_other_currencies_get_no_usd_estimate(): void
    {
        $this->seedUsdEgpRates();
        $this->seedStore();

        $this->get('/services/pharmacy-system?lang=ar')->assertOk()->assertDontSee('about USD', false);
        // the official WhatsApp service is priced in USD already, so there is nothing to estimate
        $this->get('/services/whatsapp-official?lang=en')->assertOk()->assertDontSee('about USD', false);
    }

    public function test_a_missing_rate_shows_nothing_extra_and_never_breaks_the_page(): void
    {
        CurrenciesExchange::flushCache();
        $this->seedStore();

        $this->get('/services?lang=en')->assertOk()->assertDontSee('about USD', false);
        $this->get('/services/pharmacy-system?lang=en')->assertOk()->assertDontSee('about USD', false)->assertDontSee('only an estimate', false);

        $product = SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail();
        app()->setLocale('en');
        $this->assertNull($product->approxUsdLabel(5000.0));
    }

    public function test_rounding_is_to_the_nearest_five_above_100_dollars_and_to_one_below(): void
    {
        $this->seedUsdEgpRates();
        $this->seedStore();
        app()->setLocale('en');
        $product = SoftwareProduct::where('slug', 'pharmacy-system')->firstOrFail();

        $this->assertSame('(about USD 20)', $product->approxUsdLabel(1000.0));
        $this->assertSame('(about USD 1)', $product->approxUsdLabel(10.0), 'never shows zero for a real price');
        $this->assertSame('(about USD 105)', $product->approxUsdLabel(5200.0), '104 rounds to the nearest 5');
        $this->assertNull($product->approxUsdLabel(0.0));
    }

    public function test_structured_data_and_the_checkout_amount_stay_in_egp(): void
    {
        $this->seedUsdEgpRates();
        $this->seedStore();

        $html = $this->get('/services/pharmacy-system?lang=en')->assertOk()->getContent();
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);
        $graph = collect($matches[1])->first(fn ($json) => str_contains($json, 'BreadcrumbList'));

        $this->assertStringContainsString('"priceCurrency": "EGP"', json_encode(json_decode($graph, true), JSON_PRETTY_PRINT));
        $this->assertStringNotContainsString('USD', $graph);
        $this->assertStringContainsString('(about USD', $this->get('/services/pharmacy-system.md?lang=en')->assertOk()->getContent());
    }
}
