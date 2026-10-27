<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The admin's time-limited link lets one customer see and buy a hidden program. */
class ShareLinkTest extends TestCase
{
    use RefreshDatabase;

    private function hiddenProduct(): SoftwareProduct
    {
        return SoftwareProduct::create([
            'slug' => 'private-tool',
            'name_en' => 'Private Tool',
            'name_ar' => 'أداة خاصة',
            'price' => 500,
            'currency' => 'EGP',
            'is_active' => false,
        ]);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function order()
    {
        return $this->post('/services/private-tool/order', ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000']);
    }

    public function test_admin_gets_a_link_for_the_chosen_days(): void
    {
        $product = $this->hiddenProduct();

        $url = $this->actingAs($this->admin())->postJson("/admin/software-products/{$product->id}/share", ['days' => 3])
            ->assertOk()->json('url');

        $this->assertStringContainsString('/services/private-tool/private?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertEqualsWithDelta(now()->addDays(3)->timestamp, (int) $query['expires'], 5);

        $this->actingAs($this->admin())->postJson("/admin/software-products/{$product->id}/share", ['days' => 0])->assertUnprocessable();
    }

    public function test_only_admins_can_make_a_link(): void
    {
        $product = $this->hiddenProduct();

        $this->postJson("/admin/software-products/{$product->id}/share", ['days' => 3])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['onboarding_completed' => true]))
            ->post("/admin/software-products/{$product->id}/share", ['days' => 3])->assertStatus(403);
    }

    public function test_link_opens_the_hidden_page_unindexed_and_lets_the_customer_order(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $product = $this->hiddenProduct();

        $this->get('/services/private-tool')->assertDontSee('Private Tool');
        $this->order()->assertNotFound();

        $this->get($product->shareUrl(1))->assertOk()->assertSee('Private Tool')
            ->assertSee('noindex,nofollow', false)->assertSee('client_whatsapp', false);

        $this->order()->assertRedirectContains('payments.kashier.io');
        $this->assertSame(1, SoftwareOrder::count());
    }

    public function test_expired_or_edited_link_is_refused(): void
    {
        $product = $this->hiddenProduct();
        $url = $product->shareUrl(1);

        $this->get(str_replace('private-tool', 'other-tool', $url))->assertStatus(410);

        $this->travel(25)->hours();
        $this->get($url)->assertStatus(410)->assertSee('expired');
        $this->order()->assertNotFound();
    }

    public function test_order_permission_ends_with_the_link(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $product = $this->hiddenProduct();
        $this->get($product->shareUrl(1))->assertOk();

        $this->travel(2)->days();

        $this->order()->assertNotFound();
        $this->assertSame(0, SoftwareOrder::count());
    }
}
