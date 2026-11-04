<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Currency;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

class SoftwareOrderCouponTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Currency $currency;

    private SoftwareProduct $gridReader;

    private SerialSoftware $serialSoftware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->client = User::factory()->create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'whatsapp_number' => '201015218548',
        ]);
        $this->client->assignRole('client');

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'EGP'],
            ['symbol' => 'ج.م', 'string_format' => '%v ج.م', 'rate' => 1]
        );

        $this->serialSoftware = SerialSoftware::firstOrCreate(
            ['name' => 'GridReader'],
            ['is_active' => true, 'price' => 1500, 'currency' => 'EGP', 'default_status' => 'active']
        );

        $this->seed(SoftwareProductSeeder::class);

        $this->gridReader = SoftwareProduct::where('slug', 'gridreader')->firstOrFail();
        $this->gridReader->update([
            'is_active' => true,
            'price' => 1500,
            'serial_software_id' => $this->serialSoftware->id,
        ]);
    }

    public function test_guest_cannot_use_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'FREE100',
            'name' => '100% Off',
            'type' => 'percentage',
            'discount_percentage' => 100,
            'currency_id' => $this->currency->id,
            'max_total_uses' => 1,
            'is_active' => true,
        ]);

        $response = $this->post('/services/gridreader/order', [
            'client_name' => 'Guest User',
            'client_email' => 'guest@example.com',
            'client_whatsapp' => '201000000000',
            'coupon_code' => 'FREE100',
        ]);

        $response->assertSessionHasErrors('coupon_code');
        $this->assertSame(0, (int) $coupon->fresh()->current_uses);
        $this->assertTrue((bool) $coupon->fresh()->is_active);
    }

    public function test_logged_in_user_can_use_coupon_without_refilling_form(): void
    {
        $resellerUser = User::factory()->create([
            'name' => 'Mohamed Hamed',
            'email' => 'mbestwaytravel@gmail.com',
            'whatsapp_number' => '201008833487',
        ]);
        $resellerUser->assignRole('software_reseller'); // does not have 'client' role

        $coupon = Coupon::create([
            'code' => 'FREESOFT',
            'name' => '100% Off',
            'type' => 'percentage',
            'discount_percentage' => 100,
            'currency_id' => $this->currency->id,
            'max_total_uses' => 1,
            'is_active' => true,
        ]);

        // Logged-in user submits ONLY coupon_code
        $response = $this->actingAs($resellerUser)->post('/services/gridreader/order', [
            'coupon_code' => 'FREESOFT',
        ]);

        $order = SoftwareOrder::where('client_email', $resellerUser->email)->latest('id')->firstOrFail();
        $response->assertRedirect(route('software.orders.success', $order->uuid));
        $this->assertTrue($order->isPaid());
        $this->assertSame('0.00', (string) $order->amount);
        $this->assertSame('FREESOFT', $order->coupon_code);
        $this->assertSame($resellerUser->name, $order->client_name);
        $this->assertSame($resellerUser->email, $order->client_email);
        $this->assertSame('201008833487', $order->client_whatsapp);
        $this->assertSame(1, (int) $coupon->fresh()->current_uses);
        $this->assertFalse((bool) $coupon->fresh()->is_active);
    }

    public function test_client_cannot_use_invalid_or_expired_coupon(): void
    {
        $expiredCoupon = Coupon::create([
            'code' => 'EXPIRED',
            'name' => 'Expired Coupon',
            'type' => 'percentage',
            'discount_percentage' => 50,
            'currency_id' => $this->currency->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->client)->post('/services/gridreader/order', [
            'client_name' => $this->client->name,
            'client_email' => $this->client->email,
            'client_whatsapp' => '201015218548',
            'coupon_code' => 'EXPIRED',
        ]);

        $response->assertSessionHasErrors('coupon_code');

        $inactiveCoupon = Coupon::create([
            'code' => 'INACTIVE',
            'name' => 'Inactive Coupon',
            'type' => 'percentage',
            'discount_percentage' => 50,
            'currency_id' => $this->currency->id,
            'is_active' => false,
        ]);

        $response2 = $this->actingAs($this->client)->post('/services/gridreader/order', [
            'client_name' => $this->client->name,
            'client_email' => $this->client->email,
            'client_whatsapp' => '201015218548',
            'coupon_code' => 'INACTIVE',
        ]);

        $response2->assertSessionHasErrors('coupon_code');
    }

    public function test_client_can_buy_and_download_auto_with_100_percent_coupon_and_coupon_expires(): void
    {
        Storage::fake('local');

        $release = $this->gridReader->releases()->create([
            'version' => '1.0.0',
            'file_name' => 'gridreader-setup-1.0.0.exe',
            'file_path' => 'software-releases/gridreader/gridreader-setup-1.0.0.exe',
            'sha512' => 'abc123dummy',
            'size' => 12345,
            'is_published' => true,
            'released_at' => now(),
        ]);
        Storage::disk('local')->put($release->file_path, 'dummy executable content');

        // Admin creates a 100% coupon with max_total_uses = 1
        $coupon = Coupon::create([
            'code' => 'GRID100',
            'name' => 'GridReader Free Pass',
            'type' => 'percentage',
            'discount_percentage' => 100,
            'currency_id' => $this->currency->id,
            'max_total_uses' => 1,
            'max_uses_per_user' => 1,
            'is_active' => true,
        ]);

        $deviceId = 'TESTDEVICE123456';

        // Client orders using the coupon
        $response = $this->actingAs($this->client)->post('/services/gridreader/order', [
            'client_name' => $this->client->name,
            'client_email' => $this->client->email,
            'client_whatsapp' => '201015218548',
            'device_id' => $deviceId,
            'coupon_code' => 'GRID100',
        ]);

        $order = SoftwareOrder::where('client_email', $this->client->email)->latest('id')->firstOrFail();

        // 1. Order is automatically paid without payment gateway redirect
        $response->assertRedirect(route('software.orders.success', $order->uuid));
        $this->assertTrue($order->isPaid());
        $this->assertSame('0.00', (string) $order->amount);
        $this->assertSame('1500.00', (string) $order->discount_amount);
        $this->assertSame('GRID100', $order->coupon_code);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame('coupon', $order->payment_gateway);

        // 2. License and device are automatically activated
        $this->assertNotNull($order->activated_at);
        $this->assertDatabaseHas('serial_software_licenses', [
            'user_id' => $this->client->id,
            'serial_software_id' => $this->serialSoftware->id,
            'status' => SerialSoftwareLicense::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('serial_devices', [
            'serial_software_id' => $this->serialSoftware->id,
            'device_id' => $deviceId,
            'status' => SerialDevice::STATUS_ACTIVE,
        ]);

        // 3. Coupon redemption recorded
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->client->id,
            'discount_amount' => 1500,
        ]);

        // 4. Coupon expired after single use (max_total_uses = 1)
        $freshCoupon = $coupon->fresh();
        $this->assertSame(1, (int) $freshCoupon->current_uses);
        $this->assertFalse((bool) $freshCoupon->is_active, 'Coupon should expire and become inactive after reaching max_total_uses');

        // 5. Subsequent attempt to use the coupon must fail
        $secondClient = User::factory()->create();
        $secondClient->assignRole('client');
        $secondResponse = $this->actingAs($secondClient)->post('/services/gridreader/order', [
            'client_name' => $secondClient->name,
            'client_email' => $secondClient->email,
            'client_whatsapp' => '201011111111',
            'coupon_code' => 'GRID100',
        ]);
        $secondResponse->assertSessionHasErrors('coupon_code');

        // 6. Success page renders auto-download link and script
        $successResponse = $this->get(route('software.orders.success', $order->uuid));
        $successResponse->assertOk()
            ->assertSee('download-link')
            ->assertSee(__('software.download_starting_automatically'))
            ->assertSee(__('software.download'));

        // 7. Download endpoint streams the file
        $downloadUrl = app(\Modules\SoftwareStore\Services\SoftwareOrderService::class)->downloadLink($order);
        $this->get($downloadUrl)->assertOk()->assertDownload('gridreader-setup-1.0.0.exe');
    }

    public function test_client_with_partial_discount_coupon_goes_to_payment_gateway(): void
    {
        $coupon = Coupon::create([
            'code' => 'HALF50',
            'name' => '50% Off',
            'type' => 'percentage',
            'discount_percentage' => 50,
            'currency_id' => $this->currency->id,
            'max_total_uses' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->client)->post('/services/gridreader/order', [
            'client_name' => $this->client->name,
            'client_email' => $this->client->email,
            'client_whatsapp' => '201015218548',
            'coupon_code' => 'HALF50',
        ]);

        $order = SoftwareOrder::where('client_email', $this->client->email)->latest('id')->firstOrFail();
        $this->assertSame('750.00', (string) $order->amount);
        $this->assertSame('750.00', (string) $order->discount_amount);
        $this->assertSame(SoftwareOrder::STATUS_PENDING, $order->status);
        $this->assertSame(1, (int) $coupon->fresh()->current_uses);
        $this->assertTrue((bool) $coupon->fresh()->is_active);

        $response->assertRedirectContains('payments.kashier.io');
    }

    public function test_order_form_displays_logged_in_card_and_omits_text_inputs_for_auth_user(): void
    {
        $response = $this->actingAs($this->client)->get('/services/gridreader');
        $response->assertOk();
        $response->assertSee($this->client->name);
        $response->assertSee($this->client->email);
        $response->assertSee('type="hidden" name="client_name"', false);
        $response->assertSee('id="coupon_code"', false);
    }
}
