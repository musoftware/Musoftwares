<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Angazny\AngaznyDriveImages;
use Tests\TestCase;

class AngaznyDriveImagesTest extends TestCase
{
    use RefreshDatabase;

    private const FOLDER = 'FOLDERaaaaaaaaaaaaaaaaaaaaa';

    private const SUBFOLDER = 'SUBFOLDERbbbbbbbbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public_uploads');
        config(['shop.angazny.token' => 'test-token']);
    }

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::create($overrides + [
            'angazny_id' => 3055,
            'name' => 'سوت',
            'slug' => 'suit',
            'price' => 499,
            'angazny_price' => 435,
            'images' => ['shop/products/angazny-main.jpg'],
            'is_active' => true,
            'in_stock' => true,
        ]);
    }

    private function row(string $id, string $label): string
    {
        return '<tr class="x" data-id="'.$id.'" data-target="doc"><td><div data-tooltip="'.$label.'"></div></td></tr>';
    }

    private function fakeDrive(array $overrides = []): void
    {
        Http::fake($overrides + [
            'api.angazny.com/affiliate/product/view/*' => Http::response(['data' => ['product' => ['drive_link' => 'https://drive.google.com/drive/u/0/folders/'.self::FOLDER]]]),
            'drive.google.com/drive/folders/'.self::FOLDER.'*' => Http::response(
                $this->row('photo1aaaaaaaaaaaa', 'اسود Image').$this->row('video1aaaaaaaaaaaa', 'فيديو Video').$this->row(self::SUBFOLDER, 'سيشن Folder')
            ),
            'drive.google.com/drive/folders/'.self::SUBFOLDER.'*' => Http::response($this->row('photo2aaaaaaaaaaaa', 'جلسة.jpg Image')),
            'drive.google.com/thumbnail*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    public function test_every_photo_in_the_folder_and_its_subfolders_is_added_after_the_main_image(): void
    {
        $product = $this->product();
        $this->fakeDrive();

        app(AngaznyDriveImages::class)->handle(8);

        $product->refresh();
        $this->assertSame([
            'shop/products/angazny-main.jpg',
            'shop/products/drive-photo1aaaaaaaaaaaa.jpg',
            'shop/products/drive-photo2aaaaaaaaaaaa.jpg',
        ], $product->images);
        $this->assertNotNull($product->angazny_drive_checked_at);
        Storage::disk('public_uploads')->assertExists('shop/products/drive-photo2aaaaaaaaaaaa.jpg');
    }

    public function test_a_finished_product_is_not_read_again_so_removed_photos_stay_removed(): void
    {
        $product = $this->product();
        $this->fakeDrive();
        app(AngaznyDriveImages::class)->handle(8);
        $product->refresh()->update(['images' => ['shop/products/angazny-main.jpg']]);

        app(AngaznyDriveImages::class)->handle(8);

        $this->assertCount(1, $product->refresh()->images);
    }

    public function test_a_folder_that_can_not_be_read_is_tried_again_later(): void
    {
        $product = $this->product();
        $this->fakeDrive(['drive.google.com/drive/folders/*' => Http::response('', 429)]);

        $report = app(AngaznyDriveImages::class)->handle(8);

        $this->assertSame([3055], $report['failed']);
        $this->assertNull($product->refresh()->angazny_drive_checked_at);
    }

    public function test_when_every_download_fails_the_product_is_tried_again_later(): void
    {
        $product = $this->product();
        $this->fakeDrive(['drive.google.com/thumbnail*' => Http::response('nope', 403, ['Content-Type' => 'text/html'])]);

        app(AngaznyDriveImages::class)->handle(8);

        $this->assertNull($product->refresh()->angazny_drive_checked_at);
        $this->assertCount(1, $product->images);
    }

    public function test_a_product_with_no_drive_link_is_finished(): void
    {
        $product = $this->product();
        Http::fake(['api.angazny.com/*' => Http::response(['data' => ['product' => ['drive_link' => null]]])]);

        app(AngaznyDriveImages::class)->handle(8);

        $this->assertNotNull($product->refresh()->angazny_drive_checked_at);
    }

    public function test_the_limit_is_respected_and_visible_products_go_first(): void
    {
        $hidden = $this->product(['angazny_id' => 1, 'slug' => 'a', 'is_active' => false]);
        $visible = $this->product(['angazny_id' => 2, 'slug' => 'b']);
        $this->fakeDrive();

        app(AngaznyDriveImages::class)->handle(1);

        $this->assertNotNull($visible->refresh()->angazny_drive_checked_at);
        $this->assertNull($hidden->refresh()->angazny_drive_checked_at);
    }

    public function test_the_command_does_nothing_without_a_token(): void
    {
        config(['shop.angazny.token' => null]);
        $this->product();
        Http::fake();

        $this->assertSame(0, Artisan::call('shop:angazny-drive-images'));
        Http::assertNothingSent();
    }
}
