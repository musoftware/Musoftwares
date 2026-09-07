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

    public function test_guest_invoice_show_with_loaded_currency_does_not_fail()
    {
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
            ->where('invoice.currency_symbol', '$')
        );
    }
}
