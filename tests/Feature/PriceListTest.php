<?php

namespace Tests\Feature;

use App\Models\ContractPriceItem;
use App\Models\Currency;
use App\Models\User;
use App\Services\ProjectEstimatorDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** One price list: the estimator, contracts and the AI pricing read the same items. */
class PriceListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_the_estimator_add_ons_are_in_the_price_list_at_the_same_prices(): void
    {
        $service = new ProjectEstimatorDataService;
        $builtIn = $service->getEstimatorData(50, false)['modules'];
        $fromList = $service->getEstimatorData(50)['modules'];

        foreach ($builtIn as $platform => $modules) {
            $this->assertSame(array_column($modules, 'id'), array_column($fromList[$platform], 'id'), $platform);
            $this->assertEquals(array_column($modules, 'price_usd'), array_column($fromList[$platform], 'price_usd'), $platform);
        }
    }

    public function test_a_price_edited_in_the_list_is_what_the_estimator_shows(): void
    {
        ContractPriceItem::where('key', 'web_admin_panel')->update(['price_usd' => 180]);

        $module = collect((new ProjectEstimatorDataService)->getEstimatorData(50)['modules']['web'])->firstWhere('id', 'web_admin_panel');

        $this->assertEquals(180, $module['price_usd']);
        $this->assertEquals(9000, $module['price_egp']);
    }

    public function test_an_item_without_a_fixed_price_costs_its_hours(): void
    {
        $item = new ContractPriceItem(['standalone_hours' => 4, 'price_usd' => null]);

        $this->assertEquals(100, $item->unitPriceUsd(25));
    }

    public function test_the_contract_form_can_save_a_line_as_a_new_item(): void
    {
        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'string_format' => '$%v']);

        $this->actingAs($this->admin())
            ->post('/admin/contract-price-items', ['name' => 'Telegram bot', 'default_price' => 120, 'currency_id' => $usd->id])
            ->assertSessionHasNoErrors();

        $item = ContractPriceItem::where('name', 'Telegram bot')->firstOrFail();
        $this->assertEquals(120, (float) $item->price_usd);
        $this->assertNull($item->platform);
        $this->assertSame(5, $item->standalone_hours);
    }

    public function test_the_admin_page_lists_every_item_with_its_price(): void
    {
        $items = $this->actingAs($this->admin())->getJson('/admin/contract-price-items')->assertOk()->json();

        $this->assertGreaterThanOrEqual(61, count($items));
        $this->assertTrue(collect($items)->every(fn ($item) => $item['unit_price_usd'] > 0));
    }
}
