<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestInvoiceCurrencyShowTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Feature\Concerns\SeedsUsdEgpRates;

    public function test_guest_invoice_show_with_loaded_currency_does_not_fail()
    {
        $this->seedUsdEgpRates(); // the page shows the USD total in the EGP business currency
        $currency = Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'USD',
                'symbol' => '$',
                'string_format' => '$%01.2f',
            ]
        );

        $user = User::factory()->create(['currency_id' => $currency->id]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 150,
            'cost' => 0,
            'cost_calculated' => '0',
            'is_suspended' => false,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Web Development',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 150,
        ]);

        $signedUrl = URL::signedRoute('guest.invoices.show', ['invoice' => $invoice->id]);

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/InvoiceShow')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.currency', 'USD')
            ->where('invoice.items.0.title', 'Web Development')
            ->where('invoice.remaining', fn ($value) => (float) $value === 150.0)
            ->where('invoice.time_report', null)
            // a public link: costs, margins, affiliates and the client's balance stay out
            ->missing('invoice.cost')
            ->missing('invoice.revenue')
            ->missing('invoice.margin_percentage')
            ->missing('invoice.affiliate_data')
            ->missing('invoice.user')
        );
    }

    public function test_signed_in_pay_page_uses_the_same_invoice_shape(): void
    {
        $this->seedUsdEgpRates();
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%01.2f']);
        $user = User::factory()->create(['currency_id' => $currency->id, 'onboarding_completed' => true]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 80,
            'cost' => 0,
            'cost_calculated' => '0',
            'is_suspended' => false,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Hosting', 'item_type' => 'simple', 'qty' => 2, 'amount' => 40]);

        $this->actingAs($user)->get(route('billing.invoices.pay', $invoice->uuid))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Client/Billing/InvoicePay')
                ->where('invoice.uuid', $invoice->uuid)
                ->where('invoice.items.0.title', 'Hosting')
                ->where('invoice.items.0.quantity', fn ($value) => (float) $value === 2.0)
                ->where('wallet_currency', 'USD')
            );
    }
}
