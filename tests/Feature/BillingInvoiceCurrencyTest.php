<?php

namespace Tests\Feature;

use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingInvoiceCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private const USD = 1;
    private const EGP = 2;
    private const USD_TO_EGP = 50.0;

    protected function setUp(): void
    {
        parent::setUp();

        CurrenciesExchange::flushCache();

        AdminSettings::updateOrCreate(['setting_key' => 'business_currency'], ['setting_value' => (string) self::USD]);
        AdminSettings::updateOrCreate(['setting_key' => 'exchange_update_date'], ['setting_value' => now()->toDateString()]);

        Currency::firstOrCreate(['id' => self::USD], ['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%v']);
        Currency::firstOrCreate(['id' => self::EGP], ['currency' => 'EGP', 'symbol' => 'e£', 'string_format' => 'e£%v']);

        $today = now()->toDateString();
        CurrenciesExchange::create([
            'currency1' => self::USD,
            'currency2' => self::EGP,
            'rate' => self::USD_TO_EGP,
            'date_string' => $today,
        ]);
        CurrenciesExchange::create([
            'currency1' => self::EGP,
            'currency2' => self::USD,
            'rate' => 1 / self::USD_TO_EGP,
            'date_string' => $today,
        ]);
    }

    public function test_cancelled_invoice_is_not_counted_in_total_outstanding(): void
    {
        $user = User::factory()->create([
            'currency_id' => self::EGP,
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $invoice = new Invoice();
        $invoice->uuid = (string) Str::uuid();
        $invoice->user_id = $user->id;
        $invoice->currency_id = self::USD;
        $invoice->status = 'cancelled';
        $invoice->save();

        $item = new InvoiceItem();
        $item->invoice_id = $invoice->id;
        $item->item_title = 'Cancelled Service';
        $item->qty = 1;
        $item->amount = 200;
        $item->save();

        $invoice->unpaid = $invoice->total();
        $invoice->save();

        $response = $this->actingAs($user)->get(route('billing.invoices.index'));
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Billing/Invoices')
            ->where('total_outstanding', 0)
            ->where('unpaid_invoices', [])
            ->has('invoices.data', 1)
            ->where('invoices.data.0.status', 'cancelled')
            ->where('invoices.data.0.amount', 200)
            ->where('invoices.data.0.currency.currency', 'USD')
            ->where('invoices.data.0.currency.symbol', '$')
            ->where('invoices.data.0.wallet_amount', 10000)
        );
    }

    public function test_unpaid_foreign_invoice_converts_to_wallet_currency_in_total_outstanding(): void
    {
        $user = User::factory()->create([
            'currency_id' => self::EGP,
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $invoice = new Invoice();
        $invoice->uuid = (string) Str::uuid();
        $invoice->user_id = $user->id;
        $invoice->currency_id = self::USD;
        $invoice->status = 'unpaid';
        $invoice->save();

        $item = new InvoiceItem();
        $item->invoice_id = $invoice->id;
        $item->item_title = 'Cloud Server';
        $item->qty = 1;
        $item->amount = 200;
        $item->save();

        $invoice->unpaid = $invoice->total();
        $invoice->save();

        $response = $this->actingAs($user)->get(route('billing.invoices.index'));
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Billing/Invoices')
            ->where('total_outstanding', 10000)
            ->has('unpaid_invoices', 1)
            ->where('invoices.data.0.status', 'unpaid')
            ->where('invoices.data.0.amount', 200)
            ->where('invoices.data.0.currency.currency', 'USD')
            ->where('invoices.data.0.wallet_amount', 10000)
            ->where('invoices.data.0.wallet_remaining', 10000)
        );
    }

    public function test_invoice_creation_defaults_to_client_currency(): void
    {
        $user = User::factory()->create([
            'currency_id' => self::EGP,
        ]);

        $invoice = new Invoice();
        $invoice->user_id = $user->id;
        $invoice->save();

        $this->assertEquals(self::EGP, $invoice->currency_id);
    }
}
