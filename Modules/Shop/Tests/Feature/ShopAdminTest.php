<?php

namespace Modules\Shop\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopOrder;
use Modules\Shop\Models\ShopProduct;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public_uploads');
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function productData(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'حذاء رياضي',
            'price' => 850,
            'sizes' => '40، 41 | 42',
            'colors' => '',
            'in_stock' => true,
            'is_active' => true,
            'images_add' => [UploadedFile::fake()->image('a.jpg')],
        ];
    }

    public function test_a_normal_user_can_not_open_the_store_admin(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($user)->get('/admin/shop/products')->assertForbidden();
    }

    public function test_the_admin_pages_open(): void
    {
        $admin = $this->admin();

        foreach (['products', 'products/create', 'categories', 'orders'] as $page) {
            $this->actingAs($admin)->get('/admin/shop/'.$page)->assertOk();
        }
    }

    public function test_the_admin_adds_a_product_with_sizes_and_a_picture(): void
    {
        $this->actingAs($this->admin())->post('/admin/shop/products', $this->productData())
            ->assertRedirect('/admin/shop/products');

        $product = ShopProduct::sole();
        $this->assertSame('حذاء-رياضي', $product->slug);
        $this->assertSame(['40', '41', '42'], $product->sizes);
        Storage::disk('public_uploads')->assertExists($product->images[0]);
        $this->get($product->url())->assertOk();
    }

    public function test_a_product_can_not_be_shown_without_a_picture(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/shop/products', $this->productData(['images_add' => []]))
            ->assertSessionHasErrors('images_add');
        $this->assertSame(0, ShopProduct::count());

        $hidden = ShopProduct::create(['name' => 'بدون صورة', 'slug' => 'no-picture', 'price' => 10, 'is_active' => false]);
        $this->actingAs($admin)->post('/admin/shop/products/'.$hidden->id.'/toggle')->assertSessionHasErrors('images_add');
        $this->assertFalse($hidden->fresh()->is_active);
    }

    public function test_the_old_price_must_be_higher_than_the_price(): void
    {
        $this->actingAs($this->admin())->post('/admin/shop/products', $this->productData(['compare_at_price' => 100]))
            ->assertSessionHasErrors('compare_at_price');
    }

    public function test_removing_a_picture_only_touches_pictures_of_that_product(): void
    {
        $disk = Storage::disk('public_uploads');
        $disk->put('shop/products/keep.jpg', 'x');
        $disk->put('shop/products/drop.jpg', 'x');
        $disk->put('other/secret.jpg', 'x');
        $product = ShopProduct::create(['name' => 'منتج', 'slug' => 'item', 'price' => 10, 'images' => ['shop/products/keep.jpg', 'shop/products/drop.jpg']]);

        $this->actingAs($this->admin())->post('/admin/shop/products/'.$product->id, $this->productData([
            'slug' => 'item',
            'images_add' => [],
            'images_remove' => ['shop/products/drop.jpg', 'other/secret.jpg'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['shop/products/keep.jpg'], $product->fresh()->images);
        $disk->assertMissing('shop/products/drop.jpg');
        $disk->assertExists('other/secret.jpg');
        $this->assertSame('item', $product->fresh()->slug);
    }

    public function test_deleting_keeps_the_row_and_its_address_is_not_reused(): void
    {
        $admin = $this->admin();
        $product = ShopProduct::create(['name' => 'حذاء', 'slug' => 'حذاء', 'price' => 10]);

        $this->actingAs($admin)->delete('/admin/shop/products/'.$product->id)->assertRedirect();
        $this->assertSoftDeleted($product);

        $this->actingAs($admin)->post('/admin/shop/products', $this->productData(['name' => 'حذاء']));
        $this->assertSame('حذاء-2', ShopProduct::sole()->slug);
    }

    public function test_a_category_can_be_added_and_changed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/shop/categories', ['name' => 'أحذية رجالي', 'is_active' => true])->assertSessionHasNoErrors();
        $category = ShopCategory::sole();
        $this->assertSame('أحذية-رجالي', $category->slug);

        $this->actingAs($admin)->post('/admin/shop/categories/'.$category->id, ['name' => 'أحذية', 'slug' => 'shoes', 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertSame('shoes', $category->fresh()->slug);
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_the_admin_changes_an_order_status_and_a_wrong_status_is_refused(): void
    {
        $admin = $this->admin();
        $order = ShopOrder::create(['customer_name' => 'سارة', 'phone' => '01112345678', 'governorate' => 'الجيزة', 'address' => 'عنوان طويل', 'subtotal' => 10, 'total' => 10]);

        $this->actingAs($admin)->post('/admin/shop/orders/'.$order->id.'/status', ['status' => 'shipped'])->assertSessionHasNoErrors();
        $this->assertSame('shipped', $order->fresh()->status);

        $this->actingAs($admin)->post('/admin/shop/orders/'.$order->id.'/status', ['status' => 'lost'])->assertSessionHasErrors('status');
    }

    public function test_csv_import_adds_hidden_products_skips_repeats_and_reports_bad_rows(): void
    {
        $admin = $this->admin();
        ShopProduct::create(['name' => 'موجود', 'slug' => 'existing', 'price' => 10]);
        $csv = "\xEF\xBB\xBFname,category,price,compare_at_price,sizes,colors,description\n"
            ."تيشيرت,ملابس,200,300,M|L,أسود,\"سطر أول\nسطر تاني\"\n"
            ."موجود,ملابس,100,,,,\n"
            ."بدون سعر,ملابس,abc,,,,\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($admin)->post('/admin/shop/products/import', ['file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('shop_import', fn (array $report) => $report['created'] === 1 && $report['skipped'] === 1 && count($report['problems']) === 1);

        $product = ShopProduct::where('name', 'تيشيرت')->sole();
        $this->assertFalse($product->is_active);
        $this->assertSame(['M', 'L'], $product->sizes);
        $this->assertSame("سطر أول\nسطر تاني", $product->description);
        $this->assertSame('ملابس', $product->category->name);
    }
}
