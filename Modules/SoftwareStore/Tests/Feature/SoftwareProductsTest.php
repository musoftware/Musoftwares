<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use App\Models\Currency;
use App\Models\SerialDevice;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwareLicense;
use App\Models\SerialUserDevice;
use App\Models\User;
use Modules\SoftwareStore\Services\SoftwareOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Console\GenerateAiFilesCommand;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SoftwareProductsTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = []): SoftwareProduct
    {
        return SoftwareProduct::create($overrides + [
            'slug' => 'audfinder',
            'name_en' => 'AudFinder',
            'name_ar' => 'اود فايندر',
            'price' => 500,
            'currency' => 'EGP',
            'is_active' => true,
        ]);
    }

    private function clientData(): array
    {
        return ['client_name' => 'Sara', 'client_email' => 'sara@example.com', 'client_whatsapp' => '+201000000000'];
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_old_marketplace_services_url_redirects_to_catalog(): void
    {
        $this->get('/marketplace/services?category=web')
            ->assertStatus(301)
            ->assertRedirect(route('marketplace.services.index', ['category' => 'web']));

        $this->assertSame('/marketplace/catalog', parse_url(route('marketplace.services.index'), PHP_URL_PATH));
    }

    public function test_services_page_lists_only_active_programs(): void
    {
        $this->product();
        $this->product(['slug' => 'hidden-one', 'name_en' => 'Hidden One', 'is_active' => false]);

        $this->get('/services')->assertOk()->assertSee('AudFinder')->assertDontSee('Hidden One');
    }

    public function test_each_seeded_program_has_its_own_landing_page(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true, 'price' => 300]);

        $themes = [];
        foreach (['audfinder', 'email-sender-pro', 'whatsapp-bulk-sender'] as $slug) {
            $html = $this->get("/services/{$slug}")->assertOk()->assertSee('id="order"', false)->getContent();
            $themes[] = SoftwareProduct::where('slug', $slug)->first()->themeColors()['accent'];
            $this->assertStringContainsString($themes[count($themes) - 1], $html);
        }

        $this->assertCount(3, array_unique($themes));
    }

    public function test_landing_page_renders_arabic_copy_and_gallery(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'audfinder')->update([
            'is_active' => true, 'price' => 300, 'gallery' => ['images/software/audfinder.png', 'images/software/audfinder.png'],
        ]);

        $this->withSession(['locale' => 'ar'])->get('/services/audfinder?lang=ar')
            ->assertOk()
            ->assertSee('data-lightbox', false);
    }

    public function test_program_page_shows_order_form(): void
    {
        $this->product();

        $this->get('/services/audfinder')->assertOk()->assertSee('client_whatsapp', false);
    }

    public function test_order_requires_client_details(): void
    {
        $this->product();

        $this->post('/services/audfinder/order', [])->assertSessionHasErrors(['client_name', 'client_email', 'client_whatsapp']);
        $this->assertSame(0, SoftwareOrder::count());
    }

    public function test_order_creates_pending_record_and_goes_to_payment(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->product();

        $response = $this->post('/services/audfinder/order', $this->clientData());

        $order = SoftwareOrder::firstOrFail();
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('500.00', $order->amount);
        $response->assertRedirectContains('payments.kashier.io');
    }

    public function test_order_is_refused_when_price_is_not_set(): void
    {
        $this->product(['price' => 0]);

        $this->post('/services/audfinder/order', $this->clientData())->assertSessionHas('error');
        $this->assertSame(0, SoftwareOrder::count());
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $this->postJson('/software-orders/webhook', ['data' => ['status' => 'SUCCESS']])->assertStatus(400);
    }

    public function test_paid_order_success_page_has_whatsapp_link_with_order_number(): void
    {
        $order = $this->product()->orders()->create($this->clientData() + ['amount' => 500, 'currency' => 'EGP']);
        app(SoftwareOrderService::class)->markPaid($order, 'TX-1');

        $response = $this->get(route('software.orders.success', $order->uuid))->assertOk();

        $response->assertSee('https://wa.me/201015218548', false);
        $response->assertSee($order->fresh()->reference());
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_download_needs_paid_order_and_valid_signature(): void
    {
        Storage::fake('local');
        $product = $this->product();
        Storage::disk('local')->put('software-releases/audfinder/a.exe', 'binary');
        $product->releases()->create([
            'version' => '1.0.0', 'file_path' => 'software-releases/audfinder/a.exe', 'file_name' => 'a.exe',
            'sha512' => 'x', 'size' => 6, 'is_published' => true, 'released_at' => now(),
        ]);
        $order = $product->orders()->create($this->clientData() + ['amount' => 500, 'currency' => 'EGP']);
        $service = app(SoftwareOrderService::class);

        $this->get($service->downloadLink($order))->assertForbidden();
        $this->get(route('software.orders.download', $order->uuid))->assertForbidden();

        $service->markPaid($order, 'TX-1');
        $this->get($service->downloadLink($order->fresh()))->assertOk();
    }

    public function test_admin_upload_creates_release_and_feed_exposes_it(): void
    {
        Storage::fake('local');
        $product = $this->product();

        $this->actingAs($this->admin())->post("/admin/software-products/{$product->id}/releases", [
            'files' => [UploadedFile::fake()->createWithContent('Setup 1.2.0.exe', 'installer-bytes')],
        ])->assertSessionHasNoErrors();

        $release = $product->releases()->firstOrFail();
        $expectedHash = base64_encode(hash('sha512', 'installer-bytes', true));
        $this->assertSame($expectedHash, $release->sha512);

        $yaml = $this->get('/updates/audfinder/latest.yml')->assertOk()->getContent();
        $this->assertStringContainsString('version: 1.2.0', $yaml);
        $this->assertStringContainsString("sha512: {$expectedHash}", $yaml);

        $this->get('/updates/audfinder/'.$release->file_name)->assertOk();
    }

    public function test_feed_returns_latest_published_version_and_hides_unpublished(): void
    {
        $product = $this->product();
        $base = ['file_path' => 'p', 'file_name' => 'f.exe', 'sha512' => 'h', 'size' => 1];
        $product->releases()->create($base + ['version' => '1.0.0', 'is_published' => true, 'released_at' => now()->subDay()]);
        $product->releases()->create($base + ['version' => '1.1.0', 'is_published' => false, 'released_at' => now()]);

        $this->get('/updates/audfinder/latest.yml')->assertOk()->assertSee('version: 1.0.0', false);
    }

    public function test_duplicate_version_is_refused(): void
    {
        Storage::fake('local');
        $product = $this->product();
        $admin = $this->admin();
        $payload = fn () => ['files' => [UploadedFile::fake()->createWithContent('Setup 1.0.0.exe', 'a')]];

        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/releases", $payload());
        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/releases", $payload())->assertSessionHasErrors('version');

        $this->assertSame(1, $product->releases()->count());
    }

    public function test_version_can_be_typed_when_file_name_has_none_and_blockmap_is_kept(): void
    {
        Storage::fake('local');
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/releases", [
            'files' => [UploadedFile::fake()->createWithContent('setup.exe', 'a')],
        ])->assertSessionHasErrors('version');

        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/releases", [
            'version' => '3.0.0',
            'files' => [
                UploadedFile::fake()->createWithContent('setup.exe', 'a'),
                UploadedFile::fake()->createWithContent('setup.exe.blockmap', 'map'),
            ],
        ])->assertSessionHasNoErrors();

        $release = $product->releases()->firstOrFail();
        $this->assertNotNull($release->blockmap_path);
        $this->get('/updates/audfinder/'.$release->file_name.'.blockmap')->assertOk();
    }

    private function licensedProduct(): SoftwareProduct
    {
        $software = SerialSoftware::create([
            'name' => 'AudFinder', 'requires_payment' => true, 'pricing_type' => 'single', 'price' => 1000,
            'currency' => 'EGP', 'billing_cycle' => 'annual', 'whatsapp_number' => '201015218548',
            'default_status' => 'inactive',
        ]);

        return $this->product(['serial_software_id' => $software->id, 'price' => 1, 'currency' => 'USD']);
    }

    public function test_price_and_whatsapp_come_from_the_serial_system(): void
    {
        $product = $this->licensedProduct();

        $this->assertSame(1000.0, $product->salePrice());
        $this->assertSame('EGP', $product->saleCurrency());
        $this->assertSame(365, $product->licenseDays());
        $this->assertSame('201015218548', app(SoftwareOrderService::class)->ownerWhatsappNumber($product));
    }

    public function test_payment_activates_license_and_device_by_itself(): void
    {
        $product = $this->licensedProduct();
        $order = $product->orders()->create($this->clientData() + ['device_id' => 'ABCD1234ABCD1234', 'amount' => 1000, 'currency' => 'EGP']);
        $service = app(SoftwareOrderService::class);

        $service->markPaid($order, 'TX-1');

        $order->refresh();
        $user = User::where('email', 'sara@example.com')->firstOrFail();
        $license = SerialSoftwareLicense::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($license->isActive());
        $this->assertEqualsWithDelta(365, now()->diffInDays($license->expires_at, false), 1);
        $this->assertSame($user->id, SerialUserDevice::where('device_id', 'ABCD1234ABCD1234')->value('user_id'));
        $this->assertSame('active', SerialDevice::where('device_id', 'ABCD1234ABCD1234')->value('status'));
        $this->assertNotNull($order->activated_at);

        $firstExpiry = $license->expires_at;
        $service->markPaid($order, 'TX-1');
        $this->assertEquals($firstExpiry, $license->fresh()->expires_at);
    }

    public function test_second_payment_adds_another_year_on_top(): void
    {
        $product = $this->licensedProduct();
        $service = app(SoftwareOrderService::class);

        foreach ([1, 2] as $i) {
            $order = $product->orders()->create($this->clientData() + ['amount' => 1000, 'currency' => 'EGP']);
            $service->markPaid($order, "TX-{$i}");
        }

        $license = SerialSoftwareLicense::firstOrFail();
        $this->assertEqualsWithDelta(730, now()->diffInDays($license->expires_at, false), 1);
    }

    public function test_check_in_returns_store_link_with_device_id_until_active(): void
    {
        $product = $this->licensedProduct();

        $response = $this->postJson('/api/serial/device', ['program_name' => 'AudFinder', 'device_id' => 'ABCD1234ABCD1234'])->assertOk();

        $this->assertSame($product->purchaseUrl('ABCD1234ABCD1234'), $response->json('purchase_url'));
        $this->assertStringContainsString('device=ABCD1234ABCD1234', $response->json('purchase_url'));
    }

    public function test_order_form_carries_the_device_id_from_the_link(): void
    {
        $this->licensedProduct();

        $this->get('/services/audfinder?device=ABCD1234ABCD1234')->assertOk()->assertSee('name="device_id"', false);
        $this->get('/services/audfinder')->assertOk()->assertDontSee('name="device_id"', false);
    }

    private function bundleProduct(): SoftwareProduct
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'marketing-suite')->update(['is_active' => true]);

        return SoftwareProduct::where('slug', 'marketing-suite')->firstOrFail();
    }

    public function test_bundle_has_the_four_yearly_plans_and_starts_hidden(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $bundle = SoftwareProduct::where('slug', 'marketing-suite')->firstOrFail();

        $this->assertFalse($bundle->is_active);
        $this->assertSame([4924.0, 9848.0, 13223.0, 26723.0], array_map('floatval', array_column($bundle->planList(), 'price')));
        $this->assertSame(4924.0, $bundle->startingPrice());
        $this->assertSame(13223.0, $bundle->salePrice('personal'));
    }

    public function test_reseeding_never_resets_plan_prices_or_visibility(): void
    {
        $bundle = $this->bundleProduct();
        $bundle->update(['plans' => [['key' => 'lite', 'name' => 'Lite', 'price' => 1111]]]);

        $this->seed(SoftwareProductSeeder::class);

        $bundle->refresh();
        $this->assertTrue($bundle->is_active);
        $this->assertSame(1111.0, $bundle->salePrice('lite'));
    }

    public function test_bundle_page_lists_plans_and_asks_for_a_plan(): void
    {
        $this->bundleProduct();

        $this->get('/services/marketing-suite')->assertOk()->assertSee('name="plan_key"', false)->assertSee('13,223');
        $this->get('/services')->assertOk()->assertSee('4,924');
    }

    public function test_order_requires_a_valid_plan_and_charges_that_plan(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->bundleProduct();

        $this->post('/services/marketing-suite/order', $this->clientData())->assertSessionHasErrors('plan_key');
        $this->post('/services/marketing-suite/order', $this->clientData() + ['plan_key' => 'nope'])->assertSessionHasErrors('plan_key');

        $this->post('/services/marketing-suite/order', $this->clientData() + ['plan_key' => 'basic'])->assertRedirectContains('payments.kashier.io');

        $order = SoftwareOrder::firstOrFail();
        $this->assertSame('basic', $order->plan_key);
        $this->assertSame('9848.00', $order->amount);
        $this->assertSame('EGP', $order->currency);
    }

    public function test_paid_bundle_order_shows_plan_and_has_no_license_to_activate(): void
    {
        $bundle = $this->bundleProduct();
        $order = $bundle->orders()->create($this->clientData() + ['plan_key' => 'lite', 'plan_name' => 'Lite', 'amount' => 4924, 'currency' => 'EGP']);

        app(SoftwareOrderService::class)->markPaid($order, 'TX-1');

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNull($order->activated_at);
        $this->get(route('software.orders.success', $order->uuid))->assertOk()->assertSee('Lite')->assertSee('Plan%3A%20Lite', false);
    }

    public function test_admin_list_and_edit_pages_open_and_toggle_works(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $admin = $this->admin();
        $product = SoftwareProduct::where('slug', 'audfinder')->firstOrFail();

        $this->actingAs($admin)->get('/admin/software-products')->assertOk();
        $this->actingAs($admin)->get("/admin/software-products/{$product->id}/edit")->assertOk();

        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/toggle")->assertRedirect();
        $this->assertTrue($product->fresh()->is_active);
        $this->actingAs($admin)->post("/admin/software-products/{$product->id}/toggle");
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_admin_saves_text_plans_colors_and_sections_without_json(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $product = SoftwareProduct::where('slug', 'audfinder')->firstOrFail();

        $this->actingAs($this->admin())->post("/admin/software-products/{$product->id}", [
            'name_en' => 'AudFinder', 'name_ar' => 'اود فايندر', 'price' => 1200, 'currency' => 'egp', 'is_active' => 1,
            'plans' => [
                ['key' => 'solo', 'name' => 'Solo', 'price' => 1200, 'points' => 5],
                ['key' => 'team', 'name' => 'Team', 'price' => 2400],
            ],
            'landing' => ['en' => ['headline' => 'New headline', 'problems' => ['title' => 'Why', 'items' => [['icon' => 'search', 'title' => 'A', 'body' => 'B']]]]],
            'colors' => ['accent' => '#112233', 'ink' => '#000000'],
            'sections' => ['pillars', 'problems'],
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('EGP', $product->currency);
        $this->assertSame(['solo', 'team'], array_column($product->planList(), 'key'));
        $this->assertSame('New headline', $product->landing['en']['headline']);
        $this->assertSame('#112233', $product->themeColors()['accent']);
        $this->assertSame(['pillars', 'problems'], $product->sectionOrder());

        $this->actingAs($this->admin())->post("/admin/software-products/{$product->id}", [
            'name_en' => 'AudFinder', 'name_ar' => 'اود فايندر', 'price' => 1200, 'currency' => 'EGP', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->hasPlans());
    }

    public function test_admin_rejects_a_plan_without_a_price_and_a_bad_color(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $product = SoftwareProduct::where('slug', 'audfinder')->firstOrFail();

        $this->actingAs($this->admin())->post("/admin/software-products/{$product->id}", [
            'name_en' => 'A', 'name_ar' => 'ا', 'price' => 10, 'currency' => 'EGP', 'is_active' => 1,
            'plans' => [['key' => 'x', 'name' => 'X']],
            'colors' => ['accent' => 'red'],
        ])->assertSessionHasErrors(['plans.0.price', 'colors.accent']);
    }

    public function test_bundle_is_sold_under_our_brand_and_old_address_redirects(): void
    {
        $bundle = $this->bundleProduct();

        $page = $this->get('/services/marketing-suite')->assertOk()->getContent();
        $this->assertStringNotContainsStringIgnoringCase('amctasks', $page);
        $this->assertStringNotContainsStringIgnoringCase('amc tasks', $page);
        $this->assertStringContainsString('Musoftware', $bundle->name_en);

        $this->get('/services/amc-tasks')->assertStatus(301)->assertRedirect(route('website-services.show', 'marketing-suite'));
    }

    public function test_every_page_explains_everything_in_both_languages(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);

        foreach (SoftwareProduct::all() as $product) {
            foreach (['ar', 'en'] as $lang) {
                $html = $this->withSession(['locale' => $lang])->get("/services/{$product->slug}?lang={$lang}")->assertOk()->getContent();
                $this->assertStringContainsString('<details', $html, "{$product->slug} {$lang} has no questions block");
                $this->assertStringContainsString('hreflang="ar"', $html);
                $this->assertStringContainsString('hreflang="en"', $html);
                $this->assertStringContainsString('id="order"', $html);
            }
        }
    }

    public function test_language_switch_changes_direction_and_text(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);

        $this->get('/services?lang=ar')->assertOk()->assertSee('dir="rtl"', false)->assertSee('البرامج والخدمات');
        $this->get('/services?lang=en')->assertOk()->assertSee('dir="ltr"', false)->assertSee('Programs and Services');
    }

    public function test_official_whatsapp_service_has_three_plans_priced_in_usd_and_one_payment(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $service = SoftwareProduct::where('slug', 'whatsapp-official')->firstOrFail();
        $service->update(['is_active' => true]);

        $this->assertSame('USD', $service->saleCurrency());
        $this->assertSame([46.8, 143.04, 468.14], array_map('floatval', array_column($service->planList(), 'price')));
        $this->assertSame('46.80', $service->money(46.8));
        $this->assertSame('143', $service->money(143.0));

        $this->withSession(['locale' => 'en'])->get('/services/whatsapp-official?lang=en')
            ->assertOk()->assertSee('46.80')->assertSee('one payment')->assertDontSee('/ year');
    }

    public function test_reseeding_adds_new_blocks_but_keeps_admin_edits_and_renames_old_address(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $audfinder = SoftwareProduct::where('slug', 'audfinder')->firstOrFail();
        $landing = $audfinder->landing;
        $landing['en']['headline'] = 'My own headline';
        unset($landing['en']['faq']);
        $audfinder->update(['landing' => $landing, 'name_en' => 'Renamed by admin']);

        SoftwareProduct::where('slug', 'marketing-suite')->update(['slug' => 'amc-tasks', 'name_en' => 'Old name']);
        $this->seed(SoftwareProductSeeder::class);

        $audfinder->refresh();
        $this->assertSame('My own headline', $audfinder->landing['en']['headline']);
        $this->assertSame('Renamed by admin', $audfinder->name_en);
        $this->assertNotEmpty($audfinder->landing['en']['faq']['items']);
        $this->assertSame('overview', $audfinder->sectionOrder()[0]);

        $this->assertNull(SoftwareProduct::where('slug', 'amc-tasks')->first());
        $this->assertSame('Musoftware Marketing Suite', SoftwareProduct::where('slug', 'marketing-suite')->value('name_en'));
    }

    public function test_payment_is_refused_for_a_currency_without_an_exchange_rate(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $product = $this->product(['currency' => 'OMR']);

        $this->post('/services/audfinder/order', $this->clientData())->assertSessionHas('error');
        $this->assertSame('OMR', $product->saleCurrency());
        $this->assertSame(1, SoftwareOrder::count());
        $this->assertNull(Currency::where('currency', 'OMR')->first());
    }

    /** @return array<int, array<string, mixed>> */
    private function schemaOf(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);
        $graphs = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            $this->assertNotNull($decoded, 'JSON-LD must be valid JSON');
            if (isset($decoded['@graph'])) {
                $graphs = array_merge($graphs, $decoded['@graph']);
            }
        }

        return $graphs;
    }

    public function test_every_product_page_has_complete_seo_in_server_rendered_html(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true, 'price' => 1000]);

        foreach (SoftwareProduct::all() as $product) {
            foreach (['ar', 'en'] as $lang) {
                $html = $this->withSession(['locale' => $lang])->get("/services/{$product->slug}?lang={$lang}")->assertOk()->getContent();
                $url = route('website-services.show', $product->slug);

                $canonical = $lang === 'ar' ? $url.'?lang=ar' : $url;

                $this->assertStringContainsString('<link rel="canonical" href="'.$canonical.'">', $html, "{$product->slug} canonical");
                $this->assertStringContainsString('hreflang="ar" href="'.$url.'?lang=ar"', $html);
                $this->assertStringContainsString('hreflang="en" href="'.$url.'"', $html);
                $this->assertStringContainsString('hreflang="x-default"', $html);
                $this->assertStringContainsString('type="text/markdown"', $html);
                $this->assertStringContainsString('max-image-preview:large', $html);
                $this->assertMatchesRegularExpression('~<meta name="description" content="[^"]{40,}~u', $html);
                $this->assertMatchesRegularExpression('~<meta property="og:image" content="https?://~', $html);
                $this->assertSame(1, preg_match_all('~<h1[\s>]~', $html), "{$product->slug} must have exactly one h1");

                $types = array_column($this->schemaOf($html), '@type');
                $this->assertContains('BreadcrumbList', $types);
                $this->assertContains('FAQPage', $types);
                $this->assertTrue(in_array('SoftwareApplication', $types, true) || in_array('Service', $types, true));
            }
        }
    }

    public function test_structured_data_has_real_prices_and_never_invents_ratings(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);
        SoftwareProduct::where('slug', 'audfinder')->update(['price' => 1000]);

        $graph = $this->schemaOf($this->get('/services/marketing-suite?lang=en')->getContent());
        $service = collect($graph)->firstWhere('@type', 'Service');
        $this->assertSame('AggregateOffer', $service['offers']['@type']);
        $this->assertSame('4924.00', $service['offers']['lowPrice']);
        $this->assertSame('26723.00', $service['offers']['highPrice']);
        $this->assertSame(4, $service['offers']['offerCount']);

        $official = collect($this->schemaOf($this->get('/services/whatsapp-official?lang=en')->getContent()))->firstWhere('@type', 'Service');
        $this->assertSame('USD', $official['offers']['priceCurrency']);
        $this->assertArrayNotHasKey('priceSpecification', $official['offers']['offers'][0]);

        SoftwareProduct::where('slug', 'audfinder')->first()->releases()->create([
            'version' => '2.0.2', 'file_path' => 'x', 'file_name' => 'x.exe', 'sha512' => 'h', 'size' => 1, 'is_published' => true, 'released_at' => now(),
        ]);
        $html = $this->get('/services/audfinder?lang=en')->getContent();
        $app = collect($this->schemaOf($html))->firstWhere('@type', 'SoftwareApplication');
        $this->assertSame('P1Y', $app['offers']['priceSpecification']['billingDuration']);
        $this->assertSame('Windows 10, Windows 11, macOS', $app['operatingSystem']);
        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('"review"', $html);
    }

    public function test_script_closing_text_in_content_cannot_break_out_of_json_ld(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $product = SoftwareProduct::where('slug', 'audfinder')->firstOrFail();
        $product->update(['is_active' => true, 'price' => 1000, 'tagline_en' => 'Bad </script><script>alert(1)</script>']);
        $landing = $product->landing;
        $landing['en']['subheadline'] = 'Bad </script><script>alert(1)</script>';
        $product->update(['landing' => $landing]);

        $html = $this->get('/services/audfinder?lang=en')->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertNotEmpty($this->schemaOf($html));
    }

    public function test_markdown_copy_is_served_for_active_products_only(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'marketing-suite')->update(['is_active' => true]);

        $response = $this->get('/services/marketing-suite.md?lang=en')->assertOk();
        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('rel="canonical"', $response->headers->get('Link'));
        $body = $response->getContent();
        $this->assertStringStartsWith('# Musoftware Marketing Suite', $body);
        $this->assertStringContainsString('4,924 EGP', $body);
        $this->assertStringContainsString('Common questions', $body);

        $this->get('/services/audfinder.md')->assertNotFound();
    }

    public function test_sitemap_lists_the_store_with_both_languages(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'audfinder')->update(['is_active' => true]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.route('services.index').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('website-services.show', 'audfinder').'</loc>', $xml);
        $this->assertStringContainsString('hreflang="ar" href="'.route('website-services.show', 'audfinder').'?lang=ar"', $xml);
        $this->assertStringNotContainsString(route('website-services.show', 'marketing-suite').'</loc>', $xml);
    }

    public function test_ai_files_use_the_public_address_and_describe_the_store(): void
    {
        config(['app.url' => 'http://localhost']);
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true, 'price' => 1000]);
        $command = app(GenerateAiFilesCommand::class);
        $call = fn (string $method, ...$args) => (new \ReflectionMethod($command, $method))->invoke($command, ...$args);

        $this->assertSame('https://www.musoftwares.com', $call('publicBaseUrl'));

        $english = $call('storeSection', 'https://www.musoftwares.com', 'en', true);
        $this->assertStringContainsString('https://www.musoftwares.com/services/audfinder.md?lang=en', $english);
        $this->assertStringContainsString('Common questions', $english);
        $this->assertStringNotContainsString('localhost', $english);

        $arabic = $call('storeSection', 'https://www.musoftwares.com', 'ar', false);
        $this->assertStringContainsString('البرامج والخدمات', $arabic);
    }

    public function test_the_six_systems_are_sold_as_source_code_or_with_changes_for_one_payment(): void
    {
        config(['services.kashier.merchant_id' => 'MID-1', 'services.kashier.secret_key' => 'secret', 'services.kashier.mode' => 'test']);
        $this->seed(SoftwareProductSeeder::class);
        $slugs = ['workshop-system', 'clinic-system', 'coffee-shop-system', 'gym-system', 'mobile-shop-system', 'salon-system'];

        foreach ($slugs as $slug) {
            $product = SoftwareProduct::where('slug', $slug)->firstOrFail();
            $this->assertFalse($product->is_active, 'systems start hidden until media is placed');
            $this->assertSame([5000.0, 20000.0], array_map('floatval', array_column($product->planList(), 'price')));
            $this->assertSame(['source', 'custom'], array_column($product->planList(), 'key'));
            $this->assertSame('EGP', $product->saleCurrency());
        }

        SoftwareProduct::whereIn('slug', $slugs)->update(['is_active' => true]);
        $this->get('/services/salon-system?lang=ar')->assertOk()->assertSee('الكود المصدري')->assertSee('الكود مع التعديلات')->assertSee('دفعة واحدة');

        $this->post('/services/clinic-system/order', $this->clientData() + ['plan_key' => 'custom'])->assertRedirectContains('payments.kashier.io');
        $order = SoftwareOrder::firstOrFail();
        $this->assertSame('20000.00', $order->amount);
        $this->assertSame('custom', $order->plan_key);
        $this->assertNull($order->product->serial_software_id);
    }

    public function test_pharmacy_restaurant_and_dental_systems_state_their_limits_in_both_languages(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        $slugs = ['pharmacy-system', 'restaurant-pos-system', 'dental-clinic-system'];

        foreach ($slugs as $slug) {
            $product = SoftwareProduct::where('slug', $slug)->firstOrFail();
            $this->assertFalse($product->is_active, 'systems start hidden until media is placed');
            $this->assertSame([5000.0, 20000.0], array_map('floatval', array_column($product->planList(), 'price')));
        }

        SoftwareProduct::whereIn('slug', $slugs)->update(['is_active' => true]);

        foreach ($slugs as $slug) {
            $this->get("/services/$slug?lang=ar")->assertOk()->assertSee('ETA', false)->assertSee('الكود المصدري')->assertDontSee('.test', false);
            $this->get("/services/$slug?lang=en")->assertOk()->assertSee('has not been tested against the real ETA', false)->assertDontSee('password');
        }

        $this->get('/services/pharmacy-system?lang=en')->assertSee('no official register for controlled drugs', false)->assertSee('does not collect the payment itself', false);
        $this->get('/services/restaurant-pos-system?lang=en')->assertSee('no stock control, table reservations', false);
        $this->get('/services/dental-clinic-system?lang=en')->assertSee('no DICOM', false);
    }

    public function test_new_lead_programs_have_both_languages_and_telegram_stays_hidden(): void
    {
        foreach (['maps-leads-pro', 'email-finder-pro', 'telegram-sender-pro'] as $name) {
            SerialSoftware::create(['name' => ucwords(str_replace('-', ' ', $name)), 'default_status' => 'active']);
        }
        $this->seed(SoftwareProductSeeder::class);

        foreach (['maps-leads-pro' => 1500, 'email-finder-pro' => 1500, 'telegram-sender-pro' => 1000] as $slug => $price) {
            $product = SoftwareProduct::where('slug', $slug)->firstOrFail();
            $this->assertFalse($product->is_active, 'new programs start hidden');
            $this->assertNotNull($product->serial_software_id);
            $this->assertSame((float) $price, (float) $product->price);
        }

        SoftwareProduct::whereIn('slug', ['maps-leads-pro', 'email-finder-pro'])->update(['is_active' => true]);
        $this->get('/services/maps-leads-pro?lang=ar')->assertOk()->assertSee('مابس ليدز برو')->assertSee('شروط خرائط جوجل');
        $this->get('/services/email-finder-pro?lang=en')->assertOk()->assertSee('Email Finder Pro')->assertSee('Personal Data Protection Law');
        $this->get('/services/telegram-sender-pro')->assertNotFound();
    }

    public function test_shop_manager_pro_page_is_honest_about_offline_use_and_tax(): void
    {
        SerialSoftware::create(['name' => 'Shop Manager Pro', 'default_status' => 'active']);
        $this->seed(SoftwareProductSeeder::class);

        $product = SoftwareProduct::where('slug', 'shop-manager-pro')->firstOrFail();
        $this->assertFalse($product->is_active, 'new programs start hidden');
        $this->assertNotNull($product->serial_software_id);
        $this->assertSame(1500.0, (float) $product->price);

        $product->update(['is_active' => true]);
        $this->get('/services/shop-manager-pro?lang=ar')
            ->assertOk()
            ->assertSee('مدير المحل برو')
            ->assertSee('ومفيهوش ربط مع مصلحة الضرائب');
        $this->get('/services/shop-manager-pro?lang=en')
            ->assertOk()
            ->assertSee('Shop Manager Pro')
            ->assertSee('14 days')
            ->assertSee('no connection to the Egyptian Tax Authority', false);
        $this->get('/services/shop-manager-pro.md?lang=en')->assertOk()->assertSee('one device');
    }

    public function test_guests_cannot_use_admin_software_pages(): void
    {
        $product = $this->product();

        $this->get('/admin/software-products')->assertRedirect();
        $this->post("/admin/software-products/{$product->id}/releases", ['version' => '1.0.0'])->assertRedirect();
    }
}
