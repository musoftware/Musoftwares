<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCustomHourRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$:value',
            'exchange_rate' => 1.0,
        ]);

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'client']);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->client = User::factory()->create([
            'currency_id' => $this->currency->id,
            'hour_rate_currency_id' => $this->currency->id,
            'hour_rate' => 150.00,
            'enable_custom_hour_rate' => false,
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');
    }

    public function test_admin_can_toggle_enable_custom_hour_rate_for_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('admin.users.update', ['id' => $this->client->id]), [
            'name' => 'John Doe Client',
            'email' => $this->client->email,
            'role' => 'client',
            'enable_custom_hour_rate' => true,
            'hour_rate' => 200.00,
        ]);

        $response->assertRedirect();
        $this->assertTrue((bool) $this->client->fresh()->enable_custom_hour_rate);
        $this->assertEquals(200.00, $this->client->fresh()->hour_rate);
    }

    public function test_timer_details_uses_client_custom_rate_when_enabled(): void
    {
        $this->client->update([
            'enable_custom_hour_rate' => true,
            'hour_rate' => 250.00,
        ]);
        $this->client->refresh();

        $invoice = Invoice::create([
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Development Services',
            'amount' => 100,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.invoices.timer-details', $item->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/TimerDetails')
            ->where('is_custom_rate_enabled', true)
            ->where('hour_rate', 250)
        );
    }
}
