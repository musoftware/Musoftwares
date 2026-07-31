<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppInvoiceRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;
    protected User $otherUser;
    protected User $adminUser;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Ensure we have a default currency
        Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'USD',
                'symbol' => '$',
                'string_format' => '$%01.2f',
                'is_default' => true,
            ]
        );

        $this->clientUser = User::factory()->create();
        $this->clientUser->assignRole('client');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('client');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->invoice = Invoice::create([
            'user_id' => $this->clientUser->id,
            'currency_id' => 1,
            'status' => 'unpaid',
        ]);
    }

    public function test_guest_is_redirected_to_login_with_flash_error(): void
    {
        $response = $this->get("/app/invoices/{$this->invoice->id}");

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertEquals(
            url("/app/invoices/{$this->invoice->id}"),
            session('url.intended')
        );
    }

    public function test_owner_is_redirected_to_billing_pay_page(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get("/app/invoices/{$this->invoice->id}");

        $response->assertRedirect(route('billing.invoices.pay', $this->invoice->uuid));
    }

    public function test_non_owner_receives_403_inertia_error(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->get("/app/invoices/{$this->invoice->id}");

        $response->assertStatus(403);
        $response->assertSee('This invoice belongs to another account.');
    }

    public function test_admin_is_redirected_to_admin_invoice_show_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get("/app/invoices/{$this->invoice->id}");

        $response->assertRedirect(route('admin.invoices.show', $this->invoice->id));
    }
}
