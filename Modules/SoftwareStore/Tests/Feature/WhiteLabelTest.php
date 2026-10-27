<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\Currency;
use App\Models\Earning;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use Modules\SoftwareStore\Services\RecoveryDiscount;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Tests\TestCase;

class WhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    private Currency $egp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP', 'string_format' => '%v EGP']);
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
    }

    private function program(): SoftwareProduct
    {
        $software = SerialSoftware::create([
            'name' => 'AudFinder', 'requires_payment' => true, 'pricing_type' => 'single', 'price' => 1000,
            'currency' => 'EGP', 'billing_cycle' => 'annual', 'whatsapp_number' => '201015218548', 'default_status' => 'inactive',
        ]);

        return SoftwareProduct::create([
            'slug' => 'audfinder', 'name_en' => 'AudFinder', 'name_ar' => 'اود فايندر', 'price' => 1,
            'currency' => 'EGP', 'is_active' => true, 'serial_software_id' => $software->id,
        ]);
    }

    private function partnerUser(float $balance = 0): User
    {
        return User::factory()->create(['onboarding_completed' => true, 'currency_id' => $this->egp->id, 'user_balance' => $balance]);
    }

    private function partner(array $overrides = []): WhiteLabelPartner
    {
        return WhiteLabelPartner::create($overrides + [
            'user_id' => $this->partnerUser()->id,
            'slug' => 'nile-soft',
            'brand_name' => 'Nile Soft',
            'accent_color' => '#ff5500',
            'support_whatsapp' => '+20 111 222 3333',
            'subscription_expires_at' => now()->addDays(10),
        ]);
    }

    private function clientData(): array
    {
        return ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000'];
    }

    public function test_offer_page_is_public_and_settings_need_login(): void
    {
        $this->get('/white-label')->assertOk()->assertSee(__('white_label.headline'));
        $this->get('/white-label/settings')->assertRedirect();
    }

    public function test_services_page_points_programs_to_white_label(): void
    {
        $this->program();

        $this->get('/services')->assertOk()->assertSee(route('white-label'), false);
    }

    public function test_partner_saves_brand_and_cannot_price_under_ours(): void
    {
        $product = $this->program();
        $user = $this->partnerUser();
        $brand = ['brand_name' => 'Nile Soft', 'slug' => 'nile-soft', 'accent_color' => '#FF5500'];

        $this->actingAs($user)->post('/white-label/settings', $brand + ['prices' => [$product->id => 900]])
            ->assertSessionHasErrors('prices.'.$product->id);
        $this->assertSame(0, WhiteLabelPartner::count());

        $this->actingAs($user)->post('/white-label/settings', $brand + ['prices' => [$product->id => 1300]])->assertSessionHas('success');
        $partner = WhiteLabelPartner::firstOrFail();
        $this->assertSame('#ff5500', $partner->accent_color);
        $this->assertEquals(1300, $partner->prices[$product->id]);
        $this->assertFalse($partner->isActive());

        $this->actingAs($user)->get('/white-label/settings')->assertOk()
            ->assertInertia(fn ($page) => $page->component('Client/WhiteLabel/Index')->where('partner.slug', 'nile-soft')->where('rows.0.base', 1000));
    }

    public function test_logo_is_saved_where_the_server_serves_it(): void
    {
        Storage::fake('public_uploads');
        $user = $this->partnerUser();

        $this->actingAs($user)->post('/white-label/settings', [
            'brand_name' => 'Nile Soft', 'slug' => 'nile-soft', 'accent_color' => '#ff5500',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertSessionHas('success');

        $partner = WhiteLabelPartner::firstOrFail();
        Storage::disk('public_uploads')->assertExists($partner->logo_path);
        $this->assertStringContainsString('/uploads/white-label/', $partner->logoUrl());
    }

    public function test_subscription_is_paid_from_the_wallet_and_adds_thirty_days(): void
    {
        $partner = $this->partner(['subscription_expires_at' => null]);
        $user = $partner->user;

        $this->actingAs($user)->post('/white-label/subscribe')->assertSessionHas('error', __('white_label.not_enough_balance'));
        $this->assertFalse($partner->fresh()->isActive());

        $user->forceFill(['user_balance' => 1500])->save();
        $this->actingAs($user)->post('/white-label/subscribe')->assertSessionHas('success');

        $this->assertEquals(500, (float) $user->fresh()->user_balance);
        $this->assertEqualsWithDelta(30, now()->diffInDays($partner->fresh()->subscription_expires_at), 1);
    }

    public function test_store_is_closed_without_a_subscription(): void
    {
        $this->program();
        $this->partner(['subscription_expires_at' => now()->subDay()]);

        $this->get('/shop/nile-soft')->assertNotFound();
        $this->get('/shop/nile-soft/audfinder')->assertNotFound();
    }

    public function test_store_shows_the_partner_brand_and_price_and_can_be_framed(): void
    {
        $product = $this->program();
        $partner = $this->partner();
        $partner->update(['prices' => [$product->id => 1300]]);

        $response = $this->get('/shop/nile-soft?embed=1')->assertOk();

        $response->assertSee('1,300 EGP')->assertDontSee('Musoftware');
        $response->assertSee(route('white-label.product', [$partner, 'audfinder']), false);
        $this->assertFalse($response->headers->has('X-Frame-Options'));
        $this->get('/shop/nile-soft/audfinder')->assertOk()->assertSee('Nile Soft')->assertDontSee('Musoftware');
    }

    public function test_order_charges_the_partner_price_and_fixes_their_share(): void
    {
        $product = $this->program();
        $partner = $this->partner();
        $partner->update(['prices' => [$product->id => 1300]]);

        $this->post('/shop/nile-soft/audfinder/order', $this->clientData())->assertRedirectContains('payments.kashier.io');

        $order = SoftwareOrder::firstOrFail();
        $this->assertSame($partner->id, $order->white_label_partner_id);
        $this->assertSame('1300.00', $order->amount);
        $this->assertSame('1000.00', $order->base_amount);
        $this->assertSame('600.00', $order->partner_commission); // 30% of 1,000 + 300 markup
    }

    public function test_without_a_partner_price_the_share_is_thirty_percent(): void
    {
        $this->program();
        $this->partner();

        $this->post('/shop/nile-soft/audfinder/order', $this->clientData());

        $order = SoftwareOrder::firstOrFail();
        $this->assertSame('1000.00', $order->amount);
        $this->assertSame('300.00', $order->partner_commission);
    }

    public function test_payment_records_the_share_and_the_program_shows_the_partner_brand(): void
    {
        $this->program();
        $partner = $this->partner();
        $this->post('/shop/nile-soft/audfinder/order', $this->clientData() + ['device_id' => 'ABCD1234ABCD1234']);
        $order = SoftwareOrder::firstOrFail();

        app(SoftwareOrderService::class)->markPaid($order, 'TX-1');
        app(SoftwareOrderService::class)->markPaid($order->fresh(), 'TX-1');

        $earning = Earning::sole();
        $this->assertSame($partner->user_id, $earning->user_id);
        $this->assertEquals(300, (float) $earning->amount);
        $this->assertNull($earning->transaction_id);
        $this->assertSame($partner->id, SerialSoftwareLicense::firstOrFail()->white_label_partner_id);

        $checkIn = $this->postJson('/api/serial/device', ['program_name' => 'AudFinder', 'device_id' => 'ABCD1234ABCD1234'])->assertOk();
        $this->assertSame('Nile Soft', $checkIn->json('brand.name'));
        $this->assertSame('201112223333', $checkIn->json('whatsapp_number'));

        $partner->update(['subscription_expires_at' => now()->subDay()]);
        $checkIn = $this->postJson('/api/serial/device', ['program_name' => 'AudFinder', 'device_id' => 'ABCD1234ABCD1234'])->assertOk();
        $this->assertNull($checkIn->json('brand'));
        $this->assertSame('201015218548', $checkIn->json('whatsapp_number'));
    }

    public function test_result_page_is_branded_and_sends_the_customer_to_the_partner(): void
    {
        $this->program();
        $this->partner();
        $this->post('/shop/nile-soft/audfinder/order', $this->clientData());
        $order = SoftwareOrder::firstOrFail();
        app(SoftwareOrderService::class)->markPaid($order, 'TX-1');

        $this->get(route('software.orders.success', $order->uuid))->assertOk()
            ->assertSee('Nile Soft')
            ->assertSee('https://wa.me/201112223333', false)
            ->assertDontSee('201015218548')
            ->assertDontSee('Musoftware');
    }

    public function test_partner_orders_get_no_reminder_discount_from_us(): void
    {
        $this->program();
        $this->partner();
        $this->post('/shop/nile-soft/audfinder/order', $this->clientData());
        $order = SoftwareOrder::firstOrFail();
        $order->forceFill(['reminder_sent_at' => now()])->save();

        $this->assertNull(app(RecoveryDiscount::class)->discountedAmount($order->fresh()));
    }
}
