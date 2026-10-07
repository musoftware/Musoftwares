<?php

namespace Tests\Feature;

use App\Exceptions\MissingExchangeRateException;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * No USD/EGP rates are seeded here on purpose: display pages must degrade, money paths must fail.
 */
class MissingExchangeRateDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_invoice_page_renders_with_null_business_amount_when_rate_missing(): void
    {
        $invoice = $this->usdInvoice(150);

        $this->assertNull($invoice->business_total());
        $this->assertSame('-', $invoice->business_total_str());

        $this->get(URL::signedRoute('guest.invoices.show', ['invoice' => $invoice->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('invoice.business_amount', null));
    }

    public function test_money_conversion_still_fails_when_rate_missing(): void
    {
        $this->expectException(MissingExchangeRateException::class);

        CurrenciesExchange::RateToday(100, Currency::where('currency', 'USD')->value('id'), Currency::where('currency', 'EGP')->value('id'));
    }

    private function usdInvoice(float $amount): Invoice
    {
        $usd = Currency::where('currency', 'USD')->firstOrFail();
        $user = User::factory()->create(['currency_id' => $usd->id]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $usd->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => $amount,
            'cost' => 0,
            'cost_calculated' => '0',
            'is_suspended' => false,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Work', 'item_type' => 'simple', 'qty' => 1, 'amount' => $amount]);

        return $invoice->fresh();
    }
}
