<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_currencies_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.currencies.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_currencies_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.currencies.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.currencies.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_new_currency(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currencies.store'), [
            'currency' => 'SAR',
            'symbol' => 'ر.س',
            'string_format' => '%01.2f ر.س',
        ]);

        $response->assertRedirect(route('admin.currencies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('currencies', [
            'currency' => 'SAR',
            'symbol' => 'ر.س',
            'string_format' => '%01.2f ر.س',
        ]);
    }

    public function test_store_currency_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.currencies.store'), []);

        $response->assertSessionHasErrors(['currency', 'symbol', 'string_format']);
    }

    public function test_store_currency_rejects_duplicate_code(): void
    {
        Currency::create([
            'currency' => 'SAR',
            'symbol' => 'ر.س',
            'string_format' => '%01.2f ر.س',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.currencies.store'), [
            'currency' => 'SAR',
            'symbol' => 'SR',
            'string_format' => 'SR %01.2f',
        ]);

        $response->assertSessionHasErrors('currency');
    }

    public function test_admin_can_view_edit_form(): void
    {
        $currency = Currency::create([
            'currency' => 'SAR',
            'symbol' => 'ر.س',
            'string_format' => '%01.2f ر.س',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.currencies.edit', $currency));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_currency(): void
    {
        $currency = Currency::create([
            'currency' => 'SAR',
            'symbol' => 'ر.س',
            'string_format' => '%01.2f ر.س',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.currencies.update', $currency), [
            'currency' => 'SAR',
            'symbol' => 'SR',
            'string_format' => 'SR %01.2f',
        ]);

        $response->assertRedirect(route('admin.currencies.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'symbol' => 'SR',
            'string_format' => 'SR %01.2f',
        ]);
    }

    public function test_admin_can_delete_currency(): void
    {
        $currency = Currency::create([
            'currency' => 'XYZ',
            'symbol' => 'X',
            'string_format' => '%01.2f X',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.currencies.destroy', $currency));

        $response->assertRedirect(route('admin.currencies.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }

    public function test_index_search_filters_results(): void
    {
        Currency::create(['currency' => 'SAR', 'symbol' => 'ر.س', 'string_format' => '%01.2f ر.س']);
        Currency::create(['currency' => 'JPY', 'symbol' => '¥', 'string_format' => '¥%01.2f']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.currencies.index', ['search' => 'SAR']));

        $response->assertStatus(200);
    }
}