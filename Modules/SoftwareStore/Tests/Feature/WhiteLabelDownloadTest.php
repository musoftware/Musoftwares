<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\Currency;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\WhiteLabelPartner;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Tests\TestCase;

/** A program downloaded from a partner's store shows the partner's brand from its first start, before anything is bought. */
class WhiteLabelDownloadTest extends TestCase
{
    use RefreshDatabase;

    private SerialSoftware $software;

    private SoftwareProduct $product;

    private WhiteLabelPartner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $egp = Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP', 'string_format' => '%v EGP']);

        $this->software = SerialSoftware::create([
            'name' => 'GridReader', 'requires_payment' => true, 'pricing_type' => 'single', 'price' => 1000,
            'currency' => 'EGP', 'billing_cycle' => 'annual', 'whatsapp_number' => '201015218548', 'default_status' => 'inactive',
        ]);
        $this->product = SoftwareProduct::create([
            'slug' => 'gridreader', 'name_en' => 'GridReader', 'name_ar' => 'جريد ريدر', 'price' => 1,
            'currency' => 'EGP', 'is_active' => true, 'serial_software_id' => $this->software->id,
        ]);
        Storage::disk('local')->put('software-releases/gridreader/gridreader-setup-1.0.0.exe', 'MZ-setup');
        $this->product->releases()->create([
            'version' => '1.4.0', 'file_path' => 'software-releases/gridreader/gridreader-setup-1.0.0.exe', 'file_name' => 'gridreader-setup-1.0.0.exe',
            'sha512' => 'x', 'size' => 8, 'is_published' => true, 'released_at' => now(),
        ]);
        $this->partner = WhiteLabelPartner::create([
            'user_id' => User::factory()->create(['onboarding_completed' => true, 'currency_id' => $egp->id])->id,
            'slug' => 'nile-soft', 'brand_name' => 'Nile Soft', 'accent_color' => '#ff5500',
            'support_whatsapp' => '+20 111 222 3333', 'subscription_expires_at' => now()->addDays(10),
        ]);
    }

    private function checkIn(string $deviceId, array $extra = [], string $ip = '41.10.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/serial/device', ['program_name' => 'GridReader', 'device_id' => $deviceId] + $extra)->assertOk();
    }

    public function test_store_page_offers_the_setup_named_after_the_partner(): void
    {
        $this->get('/shop/nile-soft/gridreader')->assertOk()->assertSee(route('white-label.download', [$this->partner, 'gridreader']), false);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '41.10.0.1'])->get('/shop/nile-soft/gridreader/download')->assertOk();

        $this->assertStringContainsString('gridreader-setup-1.0.0.wl-nile-soft.exe', $response->headers->get('Content-Disposition'));
        $this->assertDatabaseHas('white_label_downloads', ['white_label_partner_id' => $this->partner->id, 'ip' => '41.10.0.1']);
    }

    public function test_setup_name_gives_the_new_device_the_partner_brand_and_buy_link(): void
    {
        $checkIn = $this->checkIn('DEVICE0000000001', ['setup_name' => 'gridreader-setup-1.0.0.wl-nile-soft (1).exe'], '8.8.8.8');

        $this->assertSame('Nile Soft', $checkIn->json('brand.name'));
        $this->assertSame('201112223333', $checkIn->json('whatsapp_number'));
        $this->assertSame(route('white-label.product', [$this->partner, 'gridreader']).'?device=DEVICE0000000001', $checkIn->json('purchase_url'));

        // remembered: later check-ins after an auto-update no longer send the tagged name
        $this->assertSame('Nile Soft', $this->checkIn('DEVICE0000000001', [], '8.8.8.8')->json('brand.name'));
    }

    public function test_programs_without_the_setup_name_are_matched_by_a_recent_download_from_the_same_address(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '41.10.0.1'])->get('/shop/nile-soft/gridreader/download')->assertOk();

        $this->assertSame('Nile Soft', $this->checkIn('DEVICE0000000002')->json('brand.name'));
        $this->assertNull($this->checkIn('DEVICE0000000003', [], '41.99.0.9')->json('brand'));

        $this->travel(3)->days();
        $this->assertNull($this->checkIn('DEVICE0000000004')->json('brand'));
    }

    public function test_a_device_that_checked_in_before_the_download_keeps_our_brand(): void
    {
        $this->checkIn('DEVICE0000000005');
        $this->withServerVariables(['REMOTE_ADDR' => '41.10.0.1'])->get('/shop/nile-soft/gridreader/download');

        $checkIn = $this->checkIn('DEVICE0000000005');
        $this->assertNull($checkIn->json('brand'));
        $this->assertSame('201015218548', $checkIn->json('whatsapp_number'));
    }

    public function test_a_license_bought_from_us_shows_our_brand_and_a_closed_store_too(): void
    {
        $this->checkIn('DEVICE0000000006', ['setup_name' => 'gridreader-setup-1.0.0.wl-nile-soft.exe']);
        $user = User::factory()->create();
        SerialUserDevice::where('device_id', 'DEVICE0000000006')->update(['user_id' => $user->id]);
        SerialSoftwareLicense::create([
            'user_id' => $user->id, 'serial_software_id' => $this->software->id, 'status' => 'active', 'expires_at' => now()->addYear(),
        ]);
        $this->assertNull($this->checkIn('DEVICE0000000006')->json('brand'));

        $this->checkIn('DEVICE0000000007', ['setup_name' => 'gridreader-setup-1.0.0.wl-nile-soft.exe']);
        $this->partner->update(['subscription_expires_at' => now()->subDay()]);
        $this->assertNull($this->checkIn('DEVICE0000000007')->json('brand'));
    }

    public function test_paid_partner_order_downloads_the_tagged_setup(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->post('/shop/nile-soft/gridreader/order', ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000']);
        $order = SoftwareOrder::firstOrFail();
        $service = app(SoftwareOrderService::class);
        $service->markPaid($order, 'TX-1');

        $response = $this->get($service->downloadLink($order->fresh()))->assertOk();
        $this->assertStringContainsString('wl-nile-soft.exe', $response->headers->get('Content-Disposition'));
    }

    public function test_embed_script_places_the_store_and_fits_its_height(): void
    {
        $script = $this->get('/shop/nile-soft/embed.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8')->getContent();

        $this->assertStringContainsString(json_encode(route('white-label.store', ['partner' => $this->partner, 'embed' => 1]), JSON_UNESCAPED_SLASHES), $script);
        $this->assertStringContainsString('wl-store-height', $script);
        $this->get('/shop/nile-soft?embed=1')->assertOk()->assertSee('wl-store-height', false);
        $this->get('/shop/nile-soft')->assertOk()->assertDontSee('wl-store-height', false);

        $this->partner->update(['subscription_expires_at' => now()->subDay()]);
        $this->assertSame('', $this->get('/shop/nile-soft/embed.js')->assertOk()->getContent());
    }
}
