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
}
