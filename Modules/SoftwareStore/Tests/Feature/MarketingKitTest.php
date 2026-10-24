<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\BlogArticle;
use App\Models\User;
use App\Support\UtmLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\MarketingKit;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketingKitTest extends TestCase
{
    use RefreshDatabase;

    private const EMOJI = '/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}]/u';

    private function seedAll(): void
    {
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    /** @return list<string> every text the kit produced for one product, in both languages */
    private function allTexts(array $kit): array
    {
        $texts = [];
        foreach (['ar', 'en'] as $lang) {
            $texts = [...$texts, ...array_values($kit[$lang]['posts']), $kit[$lang]['whatsapp'], $kit[$lang]['email']['subject'], $kit[$lang]['email']['body'], ...$kit[$lang]['hashtags']];
        }

        return $texts;
    }

    public function test_utm_link_adds_parameters_and_keeps_an_existing_query(): void
    {
        $this->assertSame('https://x.test/p?utm_source=whatsapp&utm_medium=message&utm_campaign=shop', UtmLink::make('https://x.test/p', 'whatsapp', 'shop'));
        $this->assertSame('https://x.test/p?device=1&utm_source=facebook&utm_medium=social&utm_campaign=shop', UtmLink::make('https://x.test/p?device=1', 'facebook', 'shop'));
    }

    public function test_every_visible_product_gets_a_kit_in_both_languages_with_tracked_links(): void
    {
        $this->seedAll();
        $kits = app(MarketingKit::class)->forAll();

        $this->assertCount(SoftwareProduct::where('is_active', true)->count(), $kits);

        foreach ($kits as $kit) {
            $slug = $kit['slug'];
            foreach (['ar', 'en'] as $lang) {
                foreach ($kit[$lang]['posts'] as $variant => $post) {
                    $this->assertStringContainsString($kit['url'].'?utm_source=facebook&utm_medium=social&utm_campaign='.$slug, $post, "$slug $lang $variant");
                }
                $this->assertStringContainsString('utm_source=whatsapp', $kit[$lang]['whatsapp'], "$slug $lang");
                $this->assertStringContainsString('utm_source=email', $kit[$lang]['email']['body'], "$slug $lang");
                $this->assertCount(5, $kit[$lang]['hashtags'], "$slug $lang");
                $this->assertNotSame('', trim($kit[$lang]['email']['subject']));
            }
            $this->assertSame(['facebook', 'whatsapp', 'linkedin', 'x', 'email', 'groups', 'copy'], array_keys($kit['links']));
        }
    }

    public function test_kit_text_has_no_emoji_and_no_promises(): void
    {
        $this->seedAll();

        foreach (app(MarketingKit::class)->forAll() as $kit) {
            foreach ($this->allTexts($kit) as $text) {
                $this->assertDoesNotMatchRegularExpression(self::EMOJI, $text, $kit['slug']);
                $this->assertDoesNotMatchRegularExpression('/(guarantee|guaranteed|ضمان|ضامن)/iu', $text, $kit['slug']);
            }
        }
    }

    public function test_kit_uses_the_real_price_and_says_what_the_product_page_says_about_its_limits(): void
    {
        $this->seedAll();
        $kits = app(MarketingKit::class)->forAll()->keyBy('slug');

        $pharmacy = $kits['pharmacy-system'];
        $this->assertStringContainsString('5,000', $pharmacy['en']['posts']['price']);
        $this->assertStringContainsString('جنيه', $pharmacy['ar']['posts']['price']);
        $this->assertStringContainsString('Honest note:', $pharmacy['en']['posts']['price']);
        $this->assertStringContainsString('has not been tested against the real ETA', $pharmacy['en']['whatsapp']);
        $this->assertMatchesRegularExpression('/[.!?؟]\n/u', $pharmacy['en']['whatsapp'], 'the note ends on a full sentence');
        $this->assertStringContainsString('ملحوظة صريحة:', $pharmacy['ar']['whatsapp']);

        // a product whose page has no such question gets no limit line
        $this->assertStringNotContainsString('Honest note:', $kits['maps-leads-pro']['en']['posts']['price']);
    }

    public function test_the_first_post_starts_with_the_first_problem_of_the_product_page(): void
    {
        $this->seedAll();
        $product = SoftwareProduct::where('slug', 'maps-leads-pro')->firstOrFail();
        $problem = $product->landingCopy('ar')['problems']['items'][0];

        $this->assertStringStartsWith($problem['title'].': '.$problem['body'], app(MarketingKit::class)->forProduct($product)['ar']['posts']['problem']);
    }

    public function test_marketing_page_is_for_admins_only(): void
    {
        $this->seedAll();

        $this->actingAs(User::factory()->create(['onboarding_completed' => true]))->get('/admin/marketing')->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/marketing')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Admin/Marketing/Index')
                ->has('kits', SoftwareProduct::where('is_active', true)->count())
                ->has('kits.0.ar.posts.problem')
                ->has('kits.0.en.email.subject')
        );
    }

    public function test_share_links_are_on_product_and_blog_pages(): void
    {
        $this->seedAll();
        BlogArticle::create([
            'language' => 'ar', 'group_id' => 'g-share', 'title' => 'مقال للمشاركة', 'slug' => 'share-me', 'content' => '<p>نص</p>',
            'excerpt' => 'ملخص', 'is_published' => true, 'published_at' => now()->subDay(),
        ]);

        $product = $this->get('/services/pharmacy-system?lang=en')->assertOk()->assertSee('data-share', false)->getContent();
        $this->assertStringContainsString('https://wa.me/?text=', $product);
        $this->assertStringContainsString(rawurlencode('utm_source=whatsapp'), $product);
        $this->assertStringContainsString('facebook.com/sharer', $product);
        $this->assertStringContainsString('linkedin.com/sharing', $product);
        $this->assertStringContainsString('twitter.com/intent/tweet', $product);
        $this->assertStringContainsString('data-copy-link="'.url('/services/pharmacy-system').'?utm_source=copy_link', $product);

        $blog = $this->get('/blog/share-me?lang=ar')->assertOk()->assertSee('data-share', false)->assertSee('انسخ اللينك')->getContent();
        $this->assertStringContainsString(rawurlencode('utm_campaign=blog-share-me'), $blog);
        $this->assertDoesNotMatchRegularExpression(self::EMOJI, $blog);
    }
}
