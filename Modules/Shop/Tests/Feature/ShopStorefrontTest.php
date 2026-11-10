<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_the_header_keeps_extra_categories_in_a_more_menu(): void
    {
        foreach (range(1, 7) as $number) {
            $category = ShopCategory::create(['name' => 'قسم '.$number, 'slug' => 'section-'.$number]);
            $this->product(['category_id' => $category->id, 'name' => 'منتج '.$number, 'slug' => 'item-'.$number]);
        }

        $html = $this->get('/store')->assertOk()->getContent();
        preg_match('~<nav class="nav".*?</nav>~su', $html, $nav);

        $this->assertSame(5, substr_count((string) strstr($nav[0], '<details', true), '<a '));
        $this->assertSame(2, substr_count((string) strstr($nav[0], '<details'), '<a '));
    }

    public function test_the_store_has_its_own_sitemap_with_images_and_only_indexable_pages(): void
    {
        $visible = $this->product();
        $hidden = $this->product(['name' => 'مخفي', 'slug' => 'hidden-product', 'is_active' => false]);
        $empty = ShopCategory::create(['name' => 'فاضي', 'slug' => 'empty-section']);

        $this->get('/store/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('xmlns:image=', false)
            ->assertSee('<image:loc>'.$visible->mainImageUrl().'</image:loc>', false)
            ->assertSee(rawurlencode('حذاء-جلد'), false)
            ->assertSee($visible->category->url(), false)
            ->assertDontSee('hreflang', false)
            ->assertDontSee($hidden->slug, false)
            ->assertDontSee($empty->slug, false);
    }

    public function test_the_site_sitemap_points_to_the_store_sitemap_and_the_old_address_redirects(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('shop.sitemap'), false);
        $this->get('/sitemaps/store.xml')->assertRedirect('/store/sitemap.xml')->assertStatus(301);
    }

    public function test_a_shared_product_has_full_social_details(): void
    {
        $product = $this->product();

        $this->get($product->url())->assertOk()
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image" content="'.$product->mainImageUrl().'">', false)
            ->assertSee('<meta property="og:image:alt" content="حذاء جلد">', false)
            ->assertSee('<meta property="product:price:amount" content="500.00">', false)
            ->assertSee('<meta property="product:price:currency" content="EGP">', false)
            ->assertSee('<meta property="product:availability" content="in stock">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:site" content="@MusoftwareUno">', false)
            ->assertSee('<meta name="twitter:title"', false)
            ->assertSee('<meta name="twitter:label1" content="السعر">', false)
            ->assertSee('حذاء جلد بسعر 500', false);
    }

    public function test_a_shared_product_reports_the_real_size_of_its_image(): void
    {
        Storage::fake('public_uploads')->putFileAs('shop/products', UploadedFile::fake()->image('a.jpg', 800, 600), 'a.jpg');

        $this->get($this->product()->url())->assertOk()
            ->assertSee('<meta property="og:image:width" content="800">', false)
            ->assertSee('<meta property="og:image:height" content="600">', false)
            ->assertSee('<meta property="og:image:type" content="image/jpeg">', false);
    }

    public function test_product_data_is_complete_and_makes_no_false_brand_claim(): void
    {
        $product = $this->product();

        $this->get($product->url())->assertOk()
            ->assertDontSee('"brand"', false)
            ->assertSee('"@type":"OfferShippingDetails"', false)
            ->assertSee('"addressCountry":"EG"', false)
            ->assertSee('"seller"', false)
            ->assertSee('"size":"40، 41"', false);
    }

    public function test_the_store_home_has_a_share_image_and_store_data(): void
    {
        $product = $this->product();

        $this->get('/store')->assertOk()
            ->assertSee('<meta property="og:image" content="'.$product->mainImageUrl().'">', false)
            ->assertSee('"@type":"OnlineStore"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('<meta name="twitter:title"', false);
    }

    public function test_a_category_with_no_visible_products_is_hidden_from_search_and_the_menu(): void
    {
        $this->product();
        $empty = ShopCategory::create(['name' => 'فاضي', 'slug' => 'empty-section']);

        $this->get($empty->url())->assertOk()->assertSee('content="noindex,follow"', false);
        $this->get('/store')->assertOk()->assertDontSee($empty->url(), false);
    }
}
