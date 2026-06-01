<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminInvoiceControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_invoices_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.invoices.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_invoices_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.invoices.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_invoice()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.show', $invoice->id));

        $response->assertSuccessful();
    }
}
