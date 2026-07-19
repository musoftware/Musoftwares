<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $currency = Currency::first() ?? Currency::factory()->create();
        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_invoices_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_invoices_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.invoices.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_invoice(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.create', ['client_id' => $this->clientUser->id]));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->clientUser->id,
        ]);
    }

    public function test_admin_can_view_invoice_show(): void
    {
        $invoice = Invoice::createInvoice($this->clientUser, null, null);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_cancel_invoice(): void
    {
        $invoice = Invoice::createInvoice($this->clientUser, null, null);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $invoice->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_admin_can_change_invoice_status(): void
    {
        $invoice = Invoice::createInvoice($this->clientUser, null, null);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.change-status', $invoice->id), [
            'status' => 'partially_paid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('partially_paid', $invoice->fresh()->status);
    }

    public function test_admin_can_bill_invoice_from_balance(): void
    {
        $currency = Currency::first() ?? Currency::factory()->create();
        $client = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
            'user_balance' => 200,
        ]);
        $client->assignRole('client');

        $invoice = Invoice::createInvoice($client, null, null);
        $invoice->items()->create([
            'item_title' => 'Test Item',
            'amount' => 150,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $invoice->unpaid = 150;
        $invoice->save();

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.mark-paid', $invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(50, $client->fresh()->user_balance);

        $this->assertCount(1, $invoice->fresh()->transactions);
        $transaction = $invoice->transactions->first();
        $this->assertEquals(-150, $transaction->amount);
        $this->assertEquals('used', $transaction->type);
    }

    public function test_admin_cannot_bill_invoice_from_balance_if_insufficient(): void
    {
        $currency = Currency::first() ?? Currency::factory()->create();
        $client = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
            'user_balance' => 50,
        ]);
        $client->assignRole('client');

        $invoice = Invoice::createInvoice($client, null, null);
        $invoice->items()->create([
            'item_title' => 'Test Item',
            'amount' => 150,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $invoice->unpaid = 150;
        $invoice->save();

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.mark-paid', $invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('unpaid', $invoice->fresh()->status);
        $this->assertEquals(50, $client->fresh()->user_balance);
    }

    public function test_admin_can_mark_invoice_as_paid_externally(): void
    {
        $currency = Currency::first() ?? Currency::factory()->create();
        $client = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
            'user_balance' => 0,
        ]);
        $client->assignRole('client');

        $invoice = Invoice::createInvoice($client, null, null);
        $invoice->items()->create([
            'item_title' => 'Test Item',
            'amount' => 150,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $invoice->unpaid = 150;
        $invoice->save();

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.external-pay', $invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $invoice = $invoice->fresh();
        $client = $client->fresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $client->user_balance);

        $this->assertCount(2, $invoice->transactions);
        
        $receivedTrx = $invoice->transactions->where('type', 'received')->first();
        $usedTrx = $invoice->transactions->where('type', 'used')->first();

        $this->assertNotNull($receivedTrx);
        $this->assertNotNull($usedTrx);
        $this->assertEquals(150, $receivedTrx->amount);
        $this->assertEquals(-150, $usedTrx->amount);
    }
}
