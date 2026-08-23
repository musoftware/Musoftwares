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

    public function test_admin_cannot_bulk_bill_invoice_if_older_invoice_is_unpaid(): void
    {
        $currency = Currency::first() ?? Currency::factory()->create();
        $client = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $currency->id,
            'user_balance' => 1000,
        ]);
        $client->assignRole('client');

        // Create older invoice
        $olderInvoice = Invoice::createInvoice($client, null, null);
        $olderInvoice->items()->create([
            'item_title' => 'Older Invoice Item',
            'amount' => 100,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $olderInvoice->unpaid = 100;
        $olderInvoice->status = 'unpaid';
        $olderInvoice->save();

        // Create newer invoice
        $newerInvoice = Invoice::createInvoice($client, null, null);
        $newerInvoice->items()->create([
            'item_title' => 'Newer Invoice Item',
            'amount' => 200,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $newerInvoice->unpaid = 200;
        $newerInvoice->status = 'unpaid';
        $newerInvoice->save();

        // Admin attempts bulk billing on the newer invoice
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.bulk-action'), [
            'action' => 'bill_invoice',
            'invoices' => [$newerInvoice->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $sessionError = session('error');
        $this->assertStringContainsString('older invoice', $sessionError);
        $this->assertStringContainsString('is still unpaid', $sessionError);

        $this->assertEquals('unpaid', $newerInvoice->fresh()->status);
        $this->assertEquals(1000, $client->fresh()->user_balance);
    }

    public function test_admin_can_bulk_suspend_and_unsuspend_invoices()
    {
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$']);
        $client = User::factory()->create([
            'currency_id' => $currency->id,
        ]);
        $client->assignRole('client');

        $inv1 = Invoice::createInvoice($client, null, null);
        $inv1->update(['is_suspended' => false]);
        $inv2 = Invoice::createInvoice($client, null, null);
        $inv2->update(['is_suspended' => false]);

        // Bulk suspend
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.bulk-action'), [
            'action' => 'suspend',
            'invoices' => [$inv1->id, $inv2->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue((bool) $inv1->fresh()->is_suspended);
        $this->assertTrue((bool) $inv2->fresh()->is_suspended);

        // Bulk unsuspend
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.bulk-action'), [
            'action' => 'unsuspend',
            'invoices' => [$inv1->id, $inv2->id],
        ]);

        $response->assertRedirect();
        $this->assertFalse((bool) $inv1->fresh()->is_suspended);
        $this->assertFalse((bool) $inv2->fresh()->is_suspended);
    }

    public function test_admin_can_view_unpaid_invoices_tab_with_stats(): void
    {
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$']);
        $client = User::factory()->create([
            'currency_id' => $currency->id,
            'user_balance' => 500,
        ]);
        $client->assignRole('client');

        $unpaidInv = Invoice::createInvoice($client, null, null);
        $unpaidInv->items()->create([
            'item_title' => 'Test Item',
            'amount' => 150,
            'qty' => 1,
            'item_type' => 'simple',
        ]);
        $unpaidInv->unpaid = 150;
        $unpaidInv->status = 'unpaid';
        $unpaidInv->save();

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.unpaid', [
            'sort_by' => 'amount',
            'sort_dir' => 'desc',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('stats')
            ->where('currentTab', 'unpaid')
            ->has('invoices.data')
        );
    }
}
