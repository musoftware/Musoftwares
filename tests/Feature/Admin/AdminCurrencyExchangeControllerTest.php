<?php

namespace Tests\Feature\Admin;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurrencyExchangeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected Currency $usd;

    protected Currency $egp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->usd = Currency::create(['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%01.2f']);
        $this->egp = Currency::create(['currency' => 'EGP', 'symbol' => 'e£', 'string_format' => 'e£%01.2f']);
    }

    public function test_admin_can_view_exchanges_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.currency-exchanges.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_exchanges_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.currency-exchanges.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.currency-exchanges.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_new_exchange(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currency-exchanges.store'), [
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);

        $response->assertRedirect(route('admin.currency-exchanges.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('currencies_exchanges', [
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);
    }

    public function test_store_rejects_same_currency_pair(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currency-exchanges.store'), [
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->usd->id,
            'rate' => 1.0,
        ]);

        $response->assertSessionHasErrors('currency1');
    }

    public function test_store_rejects_zero_or_negative_rate(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currency-exchanges.store'), [
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0,
        ]);

        $response->assertSessionHasErrors('rate');
    }

    public function test_store_rejects_duplicate_pair_on_same_date(): void
    {
        CurrenciesExchange::create([
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.currency-exchanges.store'), [
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.021,
        ]);

        $response->assertSessionHasErrors('date_string');
    }

    public function test_store_rejects_invalid_currency_id(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currency-exchanges.store'), [
            'date_string' => '2026-07-08',
            'currency1' => 99999,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);

        $response->assertSessionHasErrors('currency1');
    }

    public function test_admin_can_update_exchange(): void
    {
        $exchange = CurrenciesExchange::create([
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.currency-exchanges.update', $exchange), [
            'date_string' => '2026-07-09',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.022,
        ]);

        $response->assertRedirect(route('admin.currency-exchanges.index'));
        $response->assertSessionHas('success');

        $exchange->refresh();
        $this->assertSame('2026-07-09', $exchange->date_string->format('Y-m-d'));
        $this->assertEqualsWithDelta(0.022, (float) $exchange->rate, 0.000001);
    }

    public function test_admin_can_delete_exchange(): void
    {
        $exchange = CurrenciesExchange::create([
            'date_string' => '2026-07-08',
            'currency1' => $this->usd->id,
            'currency2' => $this->egp->id,
            'rate' => 0.020,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.currency-exchanges.destroy', $exchange));

        $response->assertRedirect(route('admin.currency-exchanges.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('currencies_exchanges', ['id' => $exchange->id]);
    }
}
