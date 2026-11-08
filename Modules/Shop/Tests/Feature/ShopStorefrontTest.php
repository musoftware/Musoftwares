<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Models\ShopProduct;
use Tests\TestCase;

class ShopStorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $overrides = []): ShopCategory
    {
        return ShopCategory::firstOrCreate(['slug' => 'shoes'], $overrides + ['name' => 'أحذية', 'is_active' => true]);
    }

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::create($overrides + [
            'category_id' => $this->category()->id,
            'name' => 'حذاء جلد',
            'slug' => 'حذاء-جلد',
            'description' => 'وصف الحذاء',
            'price' => 500,
            'compare_at_price' => 800,
            'images' => ['shop/products/a.jpg'],
            'sizes' => ['40', '41'],
            'colors' => [],
            'in_stock' => true,
            'is_active' => true,
        ]);
    }

    private function validCustomer(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'أحمد علي',
            'phone' => '01012345678',
            'governorate' => 'القاهرة',
            'address' => 'شارع النيل، عمارة 5',
        ];
    }

    public function test_the_product_page_is_full_html_with_search_data(): void
    {
        $product = $this->product();

        $response = $this->get($product->url())->assertOk();

        $response->assertSee('<h1 class="title">حذاء جلد</h1>', false);
        $response->assertSee('<link rel="canonical" href="'.$product->url().'">', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"priceCurrency":"EGP"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('index,follow', false);
        $response->assertDontSee('data-page=', false);
    }

    public function test_a_hidden_product_or_category_is_not_found(): void
    {
        $product = $this->product(['is_active' => false]);
        $hidden = ShopCategory::create(['name' => 'مخفي', 'slug' => 'hidden', 'is_active' => false]);

        $this->get($product->url())->assertNotFound();
        $this->get($hidden->url())->assertNotFound();
    }

    public function test_a_collection_lists_its_products_and_only_the_default_order_is_indexed(): void
    {
        $product = $this->product();
        $url = $product->category->url();

        $this->get($url)->assertOk()->assertSee('حذاء جلد')->assertSee('"@type":"ItemList"', false)->assertSee('content="index,follow', false);
        $this->get($url.'?sort=price_low')->assertOk()->assertSee('content="noindex,follow"', false)->assertSee('rel="canonical" href="'.$url.'"', false);
        $this->get($url.'?page=9')->assertNotFound();
    }

    public function test_the_cart_and_checkout_stay_out_of_search_results(): void
    {
        $this->get(route('shop.cart'))->assertOk()->assertSee('noindex,nofollow', false);
    }

    public function test_the_sitemap_lists_visible_store_pages_only(): void
    {
        $visible = $this->product();
        $hidden = $this->product(['name' => 'مخفي', 'slug' => 'hidden-product', 'is_active' => false]);

        $this->get('/sitemap.xml')->assertOk()->assertSee('sitemaps/store.xml', false);
        $this->get('/sitemaps/store.xml')->assertOk()
            ->assertSee(rawurlencode('حذاء-جلد'), false)
            ->assertSee($visible->category->url(), false)
            ->assertDontSee($hidden->slug, false);
    }

    public function test_a_customer_must_choose_a_size_that_exists(): void
    {
        $product = $this->product();

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1])->assertSessionHasErrors('size');
        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'size' => '99'])->assertSessionHasErrors('size');
    }

    public function test_a_sold_out_product_can_not_be_added(): void
    {
        $product = $this->product(['in_stock' => false]);

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'size' => '40'])->assertNotFound();
    }

    public function test_a_guest_can_place_a_cash_on_delivery_order_at_the_real_price(): void
    {
        $product = $this->product();

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 2, 'size' => '41', 'price' => 1])
            ->assertRedirect(route('shop.cart'));
        $this->get(route('shop.checkout'))->assertOk()->assertSee('1,000');

        $response = $this->post(route('shop.checkout.store'), $this->validCustomer(['phone' => '٠١٠ ١٢٣٤ ٥٦٧٨']));

        $order = ShopOrder::with('items')->sole();
        $response->assertRedirect(route('shop.thanks', $order->uuid));
        $this->assertSame('01012345678', $order->phone);
        $this->assertSame('1000.00', $order->total);
        $this->assertSame('41', $order->items->sole()->size);
        $this->assertSame(2, $order->items->sole()->quantity);
        $this->get(route('shop.thanks', $order->uuid))->assertOk()->assertSee($order->number());
        $this->get(route('shop.checkout'))->assertRedirect(route('shop.cart'));
    }

    public function test_buy_now_goes_straight_to_checkout(): void
    {
        $product = $this->product();

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'size' => '40', 'buy_now' => 1])
            ->assertRedirect(route('shop.checkout'));
    }

    public function test_a_wrong_phone_number_or_a_filled_trap_field_stops_the_order(): void
    {
        $product = $this->product();
        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'size' => '40']);

        $this->post(route('shop.checkout.store'), $this->validCustomer(['phone' => '12345']))->assertSessionHasErrors('phone');
        $this->post(route('shop.checkout.store'), $this->validCustomer(['website' => 'spam']))->assertSessionHasErrors('website');
        $this->assertSame(0, ShopOrder::count());
    }

    public function test_a_product_sold_out_after_adding_is_not_ordered(): void
    {
        $product = $this->product();
        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'size' => '40']);
        $product->update(['in_stock' => false]);

        $this->post(route('shop.checkout.store'), $this->validCustomer())->assertRedirect(route('shop.cart'));
        $this->assertSame(0, ShopOrder::count());
    }

    public function test_search_finds_products_and_treats_percent_as_plain_text(): void
    {
        $this->product();

        $this->get(route('shop.search', ['q' => 'جلد']))->assertOk()->assertSee('حذاء جلد')->assertSee('noindex,nofollow', false);
        $this->get(route('shop.search', ['q' => '%']))->assertOk()->assertDontSee('حذاء جلد');
    }
}
