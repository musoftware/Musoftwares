<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Content\ProductCopyUnavailable;
use Modules\Shop\Services\Content\ProductImageArranger;
use Modules\Shop\Services\Content\ProductImageMatcher;
use Tests\TestCase;

class ProductImageArrangerTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'shop/products/main.jpg';

    private const PURPLE = 'shop/products/purple.jpg';

    private const BLACK = 'shop/products/black.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public_uploads');

        foreach ([self::MAIN, self::PURPLE, self::BLACK] as $path) {
            Storage::disk('public_uploads')->put($path, 'photo-bytes');
        }
    }

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::create($overrides + [
            'angazny_id' => 3055,
            'name' => 'سوت',
            'slug' => 'suit',
            'price' => 499,
            'images' => [self::MAIN, self::PURPLE, self::BLACK],
            'colors' => ['بنفسجي', 'اسود'],
            'sizes' => ['L'],
            'is_active' => true,
            'in_stock' => true,
            'angazny_drive_checked_at' => now(),
        ]);
    }

    private function matcher(array $answer, bool $fails = false): object
    {
        $matcher = new class($answer, $fails) implements ProductImageMatcher
        {
            public array $calls = [];

            public function __construct(private array $answer, private bool $fails) {}

            public function match(string $productName, array $colors, array $images): array
            {
                $this->calls[] = compact('productName', 'colors', 'images');
                if ($this->fails) {
                    throw new ProductCopyUnavailable('overloaded');
                }

                return $this->answer;
            }
        };
        $this->app->instance(ProductImageMatcher::class, $matcher);

        return $matcher;
    }

    private function goodAnswer(): array
    {
        return [
            'color_images' => [['color' => 'بنفسجي', 'image' => 2], ['color' => 'اسود', 'image' => 3]],
            'gallery_order' => [3, 2],
        ];
    }

    public function test_each_colour_gets_its_photo_and_the_main_photo_stays_first(): void
    {
        $product = $this->product();
        $this->matcher($this->goodAnswer());

        app(ProductImageArranger::class)->handle(5);

        $product->refresh();
        $this->assertSame([self::MAIN, self::BLACK, self::PURPLE], $product->images);
        $this->assertSame(['بنفسجي' => self::PURPLE, 'اسود' => self::BLACK], $product->color_images);
        $this->assertNotNull($product->images_arranged_at);
    }

    public function test_an_answer_can_only_point_at_this_products_own_photos_and_colours(): void
    {
        $product = $this->product();
        $this->matcher([
            'color_images' => [['color' => 'بنفسجي', 'image' => 99], ['color' => 'أخضر', 'image' => 2], ['color' => 'اسود', 'image' => 3]],
            'gallery_order' => [99, 1, 3],
        ]);

        app(ProductImageArranger::class)->handle(5);

        $product->refresh();
        $this->assertSame(['اسود' => self::BLACK], $product->color_images);
        $this->assertEqualsCanonicalizing([self::MAIN, self::PURPLE, self::BLACK], $product->images, 'no photo is lost or invented');
        $this->assertSame(self::MAIN, $product->images[0]);
    }

    public function test_a_product_waits_for_its_drive_photos(): void
    {
        $this->product(['angazny_drive_checked_at' => null]);
        $matcher = $this->matcher($this->goodAnswer());

        app(ProductImageArranger::class)->handle(5);

        $this->assertSame([], $matcher->calls);
    }

    public function test_a_product_with_one_photo_costs_nothing(): void
    {
        $product = $this->product(['images' => [self::MAIN]]);
        $matcher = $this->matcher($this->goodAnswer());

        app(ProductImageArranger::class)->handle(5);

        $this->assertSame([], $matcher->calls);
        $this->assertNotNull($product->refresh()->images_arranged_at);
    }

    public function test_when_claude_is_unavailable_the_product_is_left_for_later(): void
    {
        $product = $this->product();
        $this->matcher([], fails: true);

        $report = app(ProductImageArranger::class)->handle(5);

        $this->assertSame([3055], $report['failed']);
        $this->assertNull($product->refresh()->images_arranged_at);
    }

    public function test_the_product_page_gives_the_browser_each_colours_photo(): void
    {
        $product = $this->product(['color_images' => ['بنفسجي' => self::PURPLE]]);

        $this->get($product->url())->assertOk()->assertSee('data-color-images=', false)
            ->assertSee(str_replace('/', '\/', asset('uploads/'.self::PURPLE)), false);
    }

    public function test_a_colour_whose_photo_was_removed_falls_back_to_the_main_photo(): void
    {
        $product = $this->product(['images' => [self::MAIN], 'color_images' => ['بنفسجي' => self::PURPLE]]);

        $this->assertSame([], $product->colorImageUrls());
        $this->assertSame(asset('uploads/'.self::MAIN), $product->imageUrlFor('بنفسجي'));
    }

    public function test_the_cart_shows_the_photo_of_the_chosen_colour(): void
    {
        $product = $this->product(['color_images' => ['بنفسجي' => self::PURPLE], 'angazny_id' => null]);

        $this->post(route('shop.cart.add', $product->id), ['quantity' => 1, 'color' => 'بنفسجي', 'size' => 'L']);

        $this->get(route('shop.cart'))->assertOk()->assertSee(asset('uploads/'.self::PURPLE), false);
    }
}
