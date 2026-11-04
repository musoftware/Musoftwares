<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/**
 * The public service page must show real data only, and viewing it must never change anything.
 */
class ServicePageIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        $this->seller = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    }

    private function makeService(string $status = 'active', array $attributes = []): Service
    {
        $category = ServiceCategory::firstOrCreate(['slug' => 'web'], ['name' => 'Web']);

        return Service::create(array_merge([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'Original title',
            'description' => 'Original description',
            'status' => $status,
        ], $attributes));
    }

    private function makePackage(Service $service, float $price, int $currencyId = self::USD): ServicePackage
    {
        return ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => "Line one\nLine 'two'",
            'price' => $price,
            'currency_id' => $currencyId,
            'delivery_days' => 2,
            'revisions' => 1,
        ]);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_preview_does_not_publish_a_suspended_service(): void
    {
        $service = $this->makeService('suspended');
        $this->makePackage($service, 49);

        $this->actingAs($this->admin())->get(route('marketplace.services.show', $service->id))->assertOk();

        $this->assertSame('suspended', $service->fresh()->status);
    }

    public function test_viewing_a_service_without_packages_creates_no_package(): void
    {
        $service = $this->makeService();

        $this->get(route('marketplace.services.show', $service->id))
            ->assertOk()
            ->assertSee('not available to order', false);

        $this->assertSame(0, ServicePackage::where('service_id', $service->id)->count());
    }

    public function test_page_shows_no_fake_rating_or_verified_badge(): void
    {
        $service = $this->makeService();
        $this->makePackage($service, 49);

        $this->get(route('marketplace.services.show', $service->id) . '?lang=en')
            ->assertOk()
            ->assertDontSee('<span>5.0</span>', false)
            ->assertDontSee('Verified Software Engineer')
            ->assertSee('New service');
    }

    public function test_page_shows_the_service_faq_and_translated_text(): void
    {
        $service = $this->makeService('active', [
            'title_translations' => ['en' => 'English title', 'ar' => 'عنوان عربي'],
            'description_translations' => ['en' => 'English description', 'ar' => 'وصف عربي'],
            'faq' => [['question' => 'Do you install it?', 'answer' => 'Yes, on your server.']],
        ]);
        $this->makePackage($service, 49);

        $this->get(route('marketplace.services.show', $service->id) . '?lang=en')
            ->assertOk()
            ->assertSee('English title')
            ->assertSee('English description')
            ->assertSee('Do you install it?')
            ->assertSee('Yes, on your server.');
    }

    public function test_json_ld_cannot_close_the_script_tag(): void
    {
        $service = $this->makeService('active', ['title' => 'Bad </script><script>alert(1)</script>']);
        $this->makePackage($service, 49);

        $html = $this->get(route('marketplace.services.show', $service->id))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_balance_check_converts_the_price_to_the_buyer_currency(): void
    {
        $service = $this->makeService();
        $package = $this->makePackage($service, 100, self::USD); // 100 USD = 5000 EGP
        $buyer = User::factory()->create([
            'currency_id' => self::EGP,
            'user_balance' => 1000,
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $this->actingAs($buyer)
            ->post(route('marketplace.orders.store'), ['package_id' => $package->id])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('marketplace_orders', 0);
    }
}
