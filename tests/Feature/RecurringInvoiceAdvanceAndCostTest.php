<?php

namespace Tests\Feature;

use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringInvoiceAdvanceAndCostTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::where('currency', 'USD')->first() ?? Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f',
            'country' => 'US',
            'isocode' => 'USD',
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'currency_id' => $this->currency->id,
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_recurring_invoice_persists_cost_and_days_before(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Managed Infrastructure',
            'amount' => 500.00,
            'cost' => 150.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => '15',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('recurring_invoices', [
            'id' => $recurring->id,
            'cost' => 150.00,
            'days_before' => 3,
        ]);

        $this->assertEquals(150.00, (float) $recurring->current_cost());
        $this->assertStringContainsString('150.00', $recurring->current_cost_str());
    }

    public function test_recurring_invoice_fires_3_days_in_advance_automatically_via_apply(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(3);

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Cloud Hosting Retainer',
            'amount' => 200.00,
            'cost' => 60.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        // apply() should detect that the scheduled run in 3 days is within the 3-day window
        $recurring->apply();

        $invoice = Invoice::where('user_id', $client->id)->latest('id')->first();
        $this->assertNotNull($invoice, 'Invoice should have been generated 3 days in advance.');
        $this->assertEquals(200.00, (float) $invoice->total());
        $this->assertEquals(60.00, (float) $invoice->cost);
        $this->assertEquals('0', (string) $invoice->cost_calculated);

        $uniqueId = $recurring->id . '-' . $targetDate->toDateString();
        $this->assertDatabaseHas('recurring_invoice_records', [
            'recurring_invoice_id' => $recurring->id,
            'invoice_id' => $invoice->id,
            'unique_id' => $uniqueId,
        ]);
    }

    public function test_recurring_invoice_does_not_fire_more_than_days_before(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(4); // 4 days away (> 3 days)

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Future Retainer',
            'amount' => 200.00,
            'cost' => 50.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        $recurring->apply();

        $invoiceCount = Invoice::where('user_id', $client->id)->count();
        $this->assertEquals(0, $invoiceCount, 'Invoice should not fire when target date is 4 days away and days_before is 3.');
    }

    public function test_recurring_invoice_does_not_duplicate_when_fired_repeatedly(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(2);

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Weekly Service',
            'amount' => 150.00,
            'cost' => 40.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        $recurring->apply();
        $recurring->apply();
        $recurring->apply();

        $invoiceCount = Invoice::where('user_id', $client->id)->count();
        $this->assertEquals(1, $invoiceCount, 'Should not create duplicate invoices for the same scheduled period.');
    }

    public function test_admin_can_store_and_update_recurring_invoice_with_cost_and_days_before(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.recurring_invoices.store'), [
            'user_id' => $client->id,
            'title' => 'SEO Package',
            'amount' => 350.00,
            'cost' => 100.00,
            'days_before' => 3,
            'currency' => $this->currency->id,
            'start_date' => now()->addDay()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => ['1'],
        ]);

        $storeResponse->assertRedirect(route('admin.recurring_invoices.index'));
        $recurring = RecurringInvoice::where('title', 'SEO Package')->first();
        $this->assertNotNull($recurring);
        $this->assertEquals(100.00, (float) $recurring->cost);
        $this->assertEquals(3, (int) $recurring->days_before);

        $updateResponse = $this->actingAs($this->admin)->put(route('admin.recurring_invoices.update', $recurring->id), [
            'user_id' => $client->id,
            'title' => 'SEO Package Pro',
            'amount' => 450.00,
            'cost' => 125.00,
            'days_before' => 2,
            'currency' => $this->currency->id,
            'start_date' => now()->addDay()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => ['1'],
        ]);

        $updateResponse->assertRedirect(route('admin.recurring_invoices.index'));
        $recurring->refresh();
        $this->assertEquals('SEO Package Pro', $recurring->title);
        $this->assertEquals(450.00, (float) $recurring->amount);
        $this->assertEquals(125.00, (float) $recurring->cost);
        $this->assertEquals(2, (int) $recurring->days_before);
    }

    public function test_manual_fire_run_endpoint_succeeds_within_3_days(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(2);

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Manual Fire Test',
            'amount' => 300.00,
            'cost' => 80.00,
            'days_before' => 0, // Automated apply won't fire it in advance
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.recurring_invoices.fire_run', $recurring->id), [
            'date' => $targetDate->toDateString(),
        ]);

        $response->assertSessionHas('success');
        $invoice = Invoice::where('user_id', $client->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(300.00, (float) $invoice->total());
        $this->assertEquals(80.00, (float) $invoice->cost);
    }

    public function test_manual_fire_run_endpoint_rejects_more_than_3_days(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(5); // 5 days away (> 3)

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Manual Fire Rejected',
            'amount' => 300.00,
            'cost' => 80.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.recurring_invoices.fire_run', $recurring->id), [
            'date' => $targetDate->toDateString(),
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Invoice::where('user_id', $client->id)->count());
    }

    public function test_paying_invoice_from_recurring_invoice_materializes_cost_transaction(): void
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $targetDate = $today->copy()->addDays(1);

        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $recurring = RecurringInvoice::create([
            'user_id' => $client->id,
            'title' => 'Support Contract',
            'amount' => 250.00,
            'cost' => 75.00,
            'days_before' => 3,
            'currency_id' => $this->currency->id,
            'start_date' => $today->toDateString(),
            'current_date' => $today->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => (string) $targetDate->format('j'),
            'is_active' => true,
        ]);

        $recurring->apply();

        $invoice = Invoice::where('user_id', $client->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(75.00, (float) $invoice->cost);
        $this->assertEquals('0', (string) $invoice->cost_calculated);

        // Mark invoice as paid
        $invoice->mark_as_paid();

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('1', (string) $invoice->cost_calculated);

        // Verify CostTransaction was created and attached
        $costTx = CostTransaction::where('amount', 75.00)->first();
        $this->assertNotNull($costTx, 'CostTransaction should have been created when invoice was paid.');
        $this->assertEquals(1, $invoice->cost_transactions()->count());
    }
}
