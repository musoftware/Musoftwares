<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Models\ShopProduct;
use Modules\Shop\Services\Content\ProductContentRewriter;
use Modules\Shop\Services\Content\ProductCopyUnavailable;
use Modules\Shop\Services\Content\ProductCopyWriter;
use Tests\TestCase;

class ProductContentRewriterTest extends TestCase
{
    use RefreshDatabase;

    private const GOOD_DESCRIPTION = 'سوت قطن مريح للخروج واليوم الطويل. الخامة قطن. الدفع عند الاستلام.';

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::create($overrides + [
            'angazny_id' => 3055,
            'name' => 'سوت كود 55',
            'slug' => 'suit-55',
            'description' => 'سوت قطن من المورد',
            'price' => 499,
            'angazny_price' => 435,
            'colors' => ['اسود'],
            'sizes' => ['L'],
            'is_active' => true,
            'in_stock' => true,
        ]);
    }

    /** A writer that returns fixed text and remembers what it was given. */
    private function writer(string $name = 'سوت قطن مريح', string $description = self::GOOD_DESCRIPTION, ?string $failure = null): object
    {
        $writer = new class($name, $description, $failure) implements ProductCopyWriter
        {
            public array $facts = [];

            public function __construct(private string $name, private string $description, private ?string $failure) {}

            public function product(array $facts): array
            {
                $this->facts[] = $facts;
                if ($this->failure !== null) {
                    throw new ProductCopyUnavailable($this->failure);
                }

                return ['name' => $this->name, 'description' => $this->description];
            }

            public function category(string $name, array $productNames): string
            {
                return 'وصف قسم '.$name;
            }
        };
        $this->app->instance(ProductCopyWriter::class, $writer);

        return $writer;
    }

    public function test_supplier_text_is_replaced_and_kept_and_the_address_does_not_change(): void
    {
        $product = $this->product();
        $writer = $this->writer();

        app(ProductContentRewriter::class)->handle(5);

        $product->refresh();
        $this->assertSame('سوت قطن مريح', $product->name);
        $this->assertSame(self::GOOD_DESCRIPTION, $product->description);
        $this->assertSame('سوت كود 55', $product->original_name);
        $this->assertSame('سوت قطن من المورد', $product->original_description);
        $this->assertSame('suit-55', $product->slug);
        $this->assertSame(['اسود'], $writer->facts[0]['colors']);
    }

    public function test_a_rewritten_product_is_not_paid_for_twice(): void
    {
        $this->product();
        $writer = $this->writer();

        app(ProductContentRewriter::class)->handle(5);
        app(ProductContentRewriter::class)->handle(5);

        $this->assertCount(1, $writer->facts);
    }

    public function test_rewriting_one_product_again_uses_the_original_supplier_text(): void
    {
        $product = $this->product();
        $writer = $this->writer();
        app(ProductContentRewriter::class)->handle(5);

        app(ProductContentRewriter::class)->handle(5, 3055);

        $this->assertSame('سوت قطن من المورد', $writer->facts[1]['supplier_text']);
        $this->assertSame('سوت قطن من المورد', $product->refresh()->original_description);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function unsafeCopy(): array
    {
        return [
            'link' => ['سوت قطن', 'اتفرج على الصور هنا https://drive.google.com/x ومعاها وصف طويل كفاية للمنتج'],
            'phone' => ['سوت قطن', 'كلمنا على 01012345678 عشان تطلب ومعاها وصف طويل كفاية للمنتج ده'],
            'html' => ['سوت قطن', '<p>وصف طويل كفاية للمنتج ده وفيه علامات لازم ما تظهرش للعميل</p>'],
            'brand' => ['سوت Nike', self::GOOD_DESCRIPTION],
            'too short' => ['سوت قطن', 'قصير'],
        ];
    }

    /** @dataProvider unsafeCopy */
    public function test_text_that_breaks_a_rule_is_never_saved(string $name, string $description): void
    {
        $product = $this->product();
        $this->writer($name, $description);

        $report = app(ProductContentRewriter::class)->handle(5);

        $this->assertSame([3055], $report['failed']);
        $this->assertSame('سوت كود 55', $product->refresh()->name);
        $this->assertNull($product->content_rewritten_at);
    }

    public function test_when_claude_is_unavailable_the_product_is_left_for_later(): void
    {
        $product = $this->product();
        $this->writer(failure: 'overloaded');

        app(ProductContentRewriter::class)->handle(5);

        $this->assertSame('سوت قطن من المورد', $product->refresh()->description);
        $this->assertNull($product->content_rewritten_at);
    }

    public function test_only_categories_without_a_description_are_written(): void
    {
        $empty = ShopCategory::create(['name' => 'ملابس', 'slug' => 'clothes']);
        $written = ShopCategory::create(['name' => 'شنط', 'slug' => 'bags', 'description' => 'نص الأدمن']);
        $this->product(['category_id' => $empty->id]);
        $this->product(['angazny_id' => 2, 'slug' => 'b', 'category_id' => $written->id]);
        $this->writer();

        app(ProductContentRewriter::class)->handle(5);

        $this->assertSame('وصف قسم ملابس', $empty->refresh()->description);
        $this->assertSame('نص الأدمن', $written->refresh()->description);
    }

    public function test_the_command_does_nothing_without_a_claude_key(): void
    {
        config(['services.anthropic.key' => null]);
        $this->product();
        $writer = $this->writer();

        $this->assertSame(0, Artisan::call('shop:rewrite-content'));
        $this->assertSame([], $writer->facts);
    }
}
