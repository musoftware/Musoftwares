<?php

namespace Modules\Shop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Angazny\AngaznyCatalogueSync;
use Modules\Shop\Services\Angazny\AngaznyOrderSender;
use Modules\Shop\Services\Angazny\AngaznyPrice;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AngaznyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'api.angazny.com/affiliate';

    private const IMAGE = 'https://angazny.fra1.cdn.digitaloceanspaces.com/imgs/a.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public_uploads');
        config(['shop.angazny.token' => 'test-token', 'shop.angazny.area_ids' => [6 => 44]]);
    }

    /** One Angazny product view: colours red/blue in size L, where blue L is sold out. */
    private function angaznyView(int $id, array $product = []): array
    {
        return [
            'product' => $product + [
                'id' => $id, 'Name_AR' => 'ســوت   نسائي', 'Description' => '<p>خامة قطن</p><p>https://drive.google.com/x</p>',
                'price' => 435, 'net_commission' => 85, 'depot' => ['id' => 1, 'name' => 'المخزن الرئيسي'],
                'category_id' => 20, 'image' => self::IMAGE, 'image_gallery' => [],
            ],
            'options' => [
                ['optionName' => 'اللون', 'values' => [['id' => 1, 'value' => 'أحمر'], ['id' => 2, 'value' => 'أزرق']]],
                ['optionName' => 'المقاس', 'values' => [['id' => 3, 'value' => 'L']]],
            ],
            'stocks_id_by_path' => [['path' => [1, 3], 'id' => 501], ['path' => [2, 3], 'id' => 502]],
            'stocks_quantity_by_path' => [['path' => [3, 1], 'stock' => 7], ['path' => [2, 3], 'stock' => 0]],
        ];
    }

    /** @param array<int, array<string, mixed>|int> $views product id => view, or an HTTP error code */
    private function fakeApi(array $views): void
    {
        $responses = [
            self::API.'/product/category/list' => Http::response(['data' => [['id' => 20, 'category_name' => 'Fashion']]]),
            self::API.'/product/list/all*' => Http::response([
                'data' => array_map(fn (int $id) => ['id' => $id], array_keys($views)),
                'pagination' => ['total_pages' => 1],
            ]),
            'angazny.fra1.cdn.digitaloceanspaces.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ];

        foreach ($views as $id => $view) {
            $responses[self::API.'/product/view/'.$id] = is_int($view)
                ? Http::response([], $view)
                : Http::response(['data' => $view]);
        }

        // A second fake would only add to the first, and the first match wins.
        Http::swap(new Factory(app('events')));
        Http::fake($responses);
    }

    private function importOne(array $product = []): ShopProduct
    {
        $this->fakeApi([3055 => $this->angaznyView(3055, $product)]);
        app(AngaznyCatalogueSync::class)->handle();

        return ShopProduct::where('angazny_id', 3055)->firstOrFail();
    }

    private function buy(ShopProduct $product, string $governorate = 'القاهرة'): ShopOrder
    {
        $product->update(['is_active' => true]);
        $this->post(route('shop.cart.add', $product->id), ['quantity' => 2, 'color' => 'أحمر', 'size' => 'L'])->assertRedirect();
        $this->post(route('shop.checkout.store'), [
            'customer_name' => 'عميل تجريبي', 'phone' => '01012345678', 'governorate' => $governorate, 'address' => 'شارع النصر عمارة 5',
        ])->assertRedirect();

        return ShopOrder::with('items')->latest('id')->firstOrFail();
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_the_price_follows_the_owners_rule(): void
    {
        $this->assertSame(499.0, AngaznyPrice::forStore(435, 85));
        $this->assertSame(149.0, AngaznyPrice::forStore(100, 10), 'the least profit wins on a cheap item');
        $this->assertNull(AngaznyPrice::forStore(300, 0), 'no commission means the owner sets the price');
    }

    public function test_a_new_product_comes_in_hidden_and_cleaned_with_its_stock_codes(): void
    {
        $product = $this->importOne();

        $this->assertFalse($product->is_active);
        $this->assertSame('سوت نسائي', $product->name);
        $this->assertSame('خامة قطن', $product->description);
        $this->assertSame(499.0, (float) $product->price);
        $this->assertSame(['L'], $product->sizes);
        $this->assertSame(['أحمر'], $product->colors, 'a colour with no stock is not offered');
        $this->assertSame(501, $product->angaznyVariant('L', 'أحمر')['stock_id']);
        $this->assertSame('ملابس', $product->category->name);
        Storage::disk('public_uploads')->assertExists($product->images[0]);
    }

    public function test_stray_dots_are_removed_from_sizes(): void
    {
        $view = $this->angaznyView(3055);
        $view['options'][1]['values'][0]['value'] = '.M';
        $this->fakeApi([3055 => $view]);
        app(AngaznyCatalogueSync::class)->handle();

        $product = ShopProduct::where('angazny_id', 3055)->firstOrFail();
        $this->assertSame(['M'], $product->sizes);
        $this->assertSame(501, $product->angaznyVariant('M', 'أحمر')['stock_id']);
    }

    public function test_branded_other_warehouse_and_unclear_products_stay_out(): void
    {
        $unsupported = $this->angaznyView(3);
        $unsupported['options'][0]['optionName'] = 'Model';
        $duplicate = $this->angaznyView(4);
        $duplicate['options'][0]['values'][1]['value'] = 'أحمر';

        $this->fakeApi([
            1 => $this->angaznyView(1, ['Name_AR' => 'Adidas Samba']),
            2 => $this->angaznyView(2, ['depot' => ['id' => 9, 'name' => 'بعيد']]),
            3 => $unsupported,
            4 => $duplicate,
        ]);

        $report = app(AngaznyCatalogueSync::class)->handle();

        $this->assertSame(0, ShopProduct::count());
        $this->assertSame(['brand' => 1, 'other_warehouse' => 1, 'unsupported_options' => 1, 'unclear_options' => 1], $report['skipped']);
    }

    public function test_a_word_that_only_contains_a_brand_name_is_not_a_brand(): void
    {
        config(['shop.angazny.brands' => ['ابل']]);

        $this->assertNotNull($this->importOne(['Description' => 'قابل للغسيل']));
    }

    public function test_one_broken_product_does_not_stop_the_rest(): void
    {
        $this->fakeApi([1 => 500, 2 => $this->angaznyView(2)]);

        $report = app(AngaznyCatalogueSync::class)->handle();

        $this->assertSame([1], $report['failed']);
        $this->assertSame(1, $report['created']);
    }

    public function test_sync_refreshes_stock_but_keeps_what_the_admin_edited(): void
    {
        $product = $this->importOne();
        $product->update(['name' => 'اسم من الأدمن', 'price' => 650, 'is_active' => true]);

        $view = $this->angaznyView(3055);
        $view['stocks_quantity_by_path'] = [['path' => [1, 3], 'stock' => 0], ['path' => [2, 3], 'stock' => 4]];
        $this->fakeApi([3055 => $view]);
        app(AngaznyCatalogueSync::class)->handle();

        $product->refresh();
        $this->assertSame('اسم من الأدمن', $product->name);
        $this->assertSame(650.0, (float) $product->price);
        $this->assertSame(['أزرق'], $product->colors);
        $this->assertTrue($product->is_active);
    }

    public function test_a_price_rise_above_the_store_price_hides_the_product(): void
    {
        $product = $this->importOne();
        $product->update(['is_active' => true]);

        $this->fakeApi([3055 => $this->angaznyView(3055, ['price' => 700])]);
        app(AngaznyCatalogueSync::class)->handle();

        $product->refresh();
        $this->assertFalse($product->is_active);
        $this->assertTrue($product->angazny_needs_price);
    }

    public function test_a_product_gone_from_angazny_becomes_sold_out(): void
    {
        $product = $this->importOne();

        $this->fakeApi([]);
        app(AngaznyCatalogueSync::class)->handle();

        $this->assertFalse($product->refresh()->in_stock);
    }

    public function test_a_product_the_admin_deleted_is_not_brought_back(): void
    {
        $this->importOne()->delete();

        $this->fakeApi([3055 => $this->angaznyView(3055)]);
        $report = app(AngaznyCatalogueSync::class)->handle();

        $this->assertSame(0, ShopProduct::count());
        $this->assertSame(['deleted_by_admin' => 1], $report['skipped']);
    }

    public function test_a_product_with_no_commission_can_not_be_shown_until_priced(): void
    {
        $product = $this->importOne(['net_commission' => 0]);
        $admin = $this->admin();

        $this->assertTrue($product->angazny_needs_price);
        $this->actingAs($admin)->post("/admin/shop/products/{$product->id}/toggle")->assertSessionHasErrors('images_add');
        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_the_admin_can_not_price_under_angaznys_price(): void
    {
        $product = $this->importOne();

        $this->actingAs($this->admin())->post("/admin/shop/products/{$product->id}", [
            'name' => $product->name, 'price' => 400, 'in_stock' => true, 'is_active' => false,
        ])->assertSessionHasErrors('price');
    }

    public function test_an_order_keeps_the_stock_code_shipping_and_store_profit(): void
    {
        $order = $this->buy($this->importOne());
        $item = $order->items->first();

        $this->assertSame('pending', $order->angazny_status);
        $this->assertSame(80.0, (float) $order->shipping);
        $this->assertSame(1078.0, (float) $order->total);
        $this->assertSame(501, $item->angazny_stock_id);
        $this->assertSame(64.0, (float) $item->angazny_price_effect);
    }

    public function test_a_sold_out_combination_can_not_be_bought(): void
    {
        $product = $this->importOne();
        $product->update(['is_active' => true, 'colors' => ['أحمر', 'أزرق']]);

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'color' => 'أزرق', 'size' => 'L'])->assertSessionHasErrors('size');
    }

    public function test_a_governorate_angazny_does_not_reach_is_refused(): void
    {
        $product = $this->importOne();
        $product->update(['is_active' => true]);
        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'color' => 'أحمر', 'size' => 'L']);

        $this->post(route('shop.checkout.store'), [
            'customer_name' => 'عميل تجريبي', 'phone' => '01012345678', 'governorate' => 'مطروح', 'address' => 'شارع النصر عمارة 5',
        ])->assertSessionHasErrors('governorate');
    }

    public function test_sending_an_order_posts_the_right_data_once(): void
    {
        $order = $this->buy($this->importOne());
        Http::fake([self::API.'/order/create' => Http::response(['status' => 200, 'order_code' => 'EGY9', 'itamsOutOfStock' => 0, 'itamsNotFound' => 0])]);

        app(AngaznyOrderSender::class)->send($order);
        app(AngaznyOrderSender::class)->send($order->refresh());

        $this->assertSame('sent', $order->refresh()->angazny_status);
        $this->assertSame('EGY9', $order->angazny_order_code);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['city_id'] === 6
            && $request['area_id'] === 44
            && $request['phone'] === ['+201012345678']
            && $request['items'] === [['stock_id' => 501, 'quantity' => 2, 'price_effect' => 64.0]]);
    }

    public function test_a_lost_answer_is_never_resent_automatically(): void
    {
        $order = $this->buy($this->importOne());
        $attempts = 0;
        Http::fake(function () use (&$attempts): void {
            $attempts++;

            throw new ConnectionException('timed out');
        });

        app(AngaznyOrderSender::class)->send($order);
        app(AngaznyOrderSender::class)->send($order->refresh());

        $this->assertSame('unknown', $order->refresh()->angazny_status);
        $this->assertSame(1, $attempts);
    }

    public function test_a_server_error_is_treated_as_unknown(): void
    {
        $order = $this->buy($this->importOne());
        Http::fake([self::API.'/order/create' => Http::response([], 500)]);

        app(AngaznyOrderSender::class)->send($order);

        $this->assertSame('unknown', $order->refresh()->angazny_status);
    }

    public function test_a_refused_order_shows_why_and_can_be_sent_again(): void
    {
        $order = $this->buy($this->importOne());
        Http::fake([self::API.'/order/create' => Http::response(['messages' => ['العنوان ناقص']], 400)]);

        app(AngaznyOrderSender::class)->send($order);

        $this->assertSame('failed', $order->refresh()->angazny_status);
        $this->assertSame('العنوان ناقص', $order->angazny_error);
        $this->assertContains($order->angazny_status, ShopOrder::ANGAZNY_SENDABLE);
    }

    public function test_an_order_with_no_area_code_is_not_sent(): void
    {
        config(['shop.angazny.area_ids' => []]);
        $order = $this->buy($this->importOne());
        Http::fake();

        app(AngaznyOrderSender::class)->send($order);

        $this->assertSame('failed', $order->refresh()->angazny_status);
        Http::assertNothingSent();
    }

    public function test_a_cancelled_order_is_not_sent(): void
    {
        $order = $this->buy($this->importOne());
        $order->update(['status' => 'cancelled']);
        Http::fake();

        app(AngaznyOrderSender::class)->send($order);

        Http::assertNothingSent();
    }

    public function test_automatic_sending_stays_off_until_switched_on(): void
    {
        $order = $this->buy($this->importOne());
        Http::fake([self::API.'/order/create' => Http::response(['order_code' => 'EGY9'])]);

        Artisan::call('shop:angazny-send-orders');
        $this->assertSame('pending', $order->refresh()->angazny_status);

        config(['shop.angazny.forward_orders' => true]);
        Artisan::call('shop:angazny-send-orders');
        $this->assertSame('sent', $order->refresh()->angazny_status);
    }

    public function test_the_admin_can_send_one_order_by_hand(): void
    {
        $order = $this->buy($this->importOne());
        Http::fake([self::API.'/order/create' => Http::response(['order_code' => 'EGY7'])]);

        $this->actingAs($this->admin())->post("/admin/shop/orders/{$order->id}/angazny")->assertRedirect();

        $this->assertSame('EGY7', $order->refresh()->angazny_order_code);
    }

    public function test_sync_does_nothing_without_a_token(): void
    {
        config(['shop.angazny.token' => null]);
        Http::fake();

        $this->assertSame(0, Artisan::call('shop:angazny-sync'));
        Http::assertNothingSent();
    }
}
