<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\FreeQrMenu;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\FreeTools;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class FreeToolsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private function menuPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cafe Nile',
            'tagline' => 'Coffee by the river',
            'phone' => '+20 100 123 4567',
            'currency' => 'EGP',
            'color' => '#b45309',
            'lang' => 'ar',
            'sections' => [
                ['title' => 'Hot drinks', 'items' => [['name' => 'Espresso', 'price' => 45, 'note' => 'Double shot'], ['name' => 'Tea', 'price' => null]]],
            ],
        ], $overrides);
    }

    public function test_every_tool_page_renders_in_both_languages_with_its_seo(): void
    {
        foreach (FreeTools::slugs() as $slug) {
            $this->get("/services/tools/{$slug}?lang=en")
                ->assertOk()
                ->assertSee(__("free_tools.tools.{$slug}.name", [], 'en'))
                ->assertSee('<link rel="canonical" href="'.url("/services/tools/{$slug}").'">', false)
                ->assertSee('"@type":"FAQPage"', false)
                ->assertSee('hreflang="ar"', false);

            $this->get("/services/tools/{$slug}?lang=ar")->assertOk()->assertSee(__("free_tools.tools.{$slug}.name", [], 'ar'));
        }
    }

    public function test_every_visit_counts_and_the_counter_is_public(): void
    {
        $browser = ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'];

        $this->get('/services/tools/vat-calculator?lang=en', $browser)->assertSee('1 views');
        $this->get('/services/tools/vat-calculator?lang=en', $browser)->assertSee('2 views');
        $this->get('/services/tools/vat-calculator?lang=en', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->assertSee('2 views');

        $this->assertSame(2, FreeTools::viewsOf('vat-calculator'));
        $this->get('/services?lang=en')->assertSee('2 views')->assertSee('0 views');
        $this->get('/services?lang=ar')->assertSee('2 مشاهدة');
    }

    public function test_unknown_tool_is_not_found(): void
    {
        $this->get('/services/tools/nope')->assertNotFound();
    }

    public function test_tool_pages_link_to_their_related_products(): void
    {
        $this->seedUsdEgpRates();
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::query()->update(['is_active' => true]);

        $this->get('/services/tools/whatsapp-link-generator')->assertOk()->assertSee('/services/whatsapp-bulk-sender', false);
    }

    public function test_services_page_lists_the_free_tools(): void
    {
        $this->get('/services')->assertOk()->assertSee('id="tools"', false)->assertSee('/services/tools/qr-menu-maker', false);
        $this->get('/services?lang=ar')->assertOk()->assertSee('أدوات مجانية');
    }

    public function test_sitemap_lists_every_tool(): void
    {
        $xml = $this->sitemapXml();

        foreach (FreeTools::slugs() as $slug) {
            $this->assertStringContainsString(url("/services/tools/{$slug}"), $xml);
        }
    }

    public function test_old_tool_addresses_redirect_to_the_new_tools(): void
    {
        $this->get('/tools')->assertStatus(301)->assertRedirect('/services#tools');
        $this->get('/tools/invoice-generator')->assertStatus(301)->assertRedirect('/services/tools/invoice-generator');
        $this->get('/services/tools')->assertStatus(301)->assertRedirect('/services#tools');
    }

    public function test_a_menu_is_created_shown_and_counted(): void
    {
        $json = $this->postJson('/services/tools/qr-menu', $this->menuPayload())->assertCreated()->json();

        $this->assertMatchesRegularExpression('~^[a-z0-9]{8}$~', $json['code']);
        $this->assertSame(url('/menu/'.$json['code']), $json['url']);

        $this->get('/menu/'.$json['code'])
            ->assertOk()
            ->assertSee('Cafe Nile')
            ->assertSee('Espresso')
            ->assertSee('45 EGP')
            ->assertSee('noindex, nofollow', false)
            ->assertSee('dir="rtl"', false);

        $this->assertSame(1, FreeQrMenu::first()->views);
    }

    public function test_menu_text_is_escaped_and_the_color_cannot_inject_css(): void
    {
        $code = $this->postJson('/services/tools/qr-menu', $this->menuPayload(['name' => '<script>alert(1)</script>']))->assertCreated()->json('code');

        $this->get('/menu/'.$code)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);

        $this->postJson('/services/tools/qr-menu', $this->menuPayload(['color' => 'red;}body{display:none']))->assertStatus(422);
        $this->postJson('/services/tools/qr-menu', $this->menuPayload(['phone' => 'javascript:alert(1)']))->assertStatus(422);
    }

    public function test_only_the_edit_token_can_read_or_change_a_menu(): void
    {
        $json = $this->postJson('/services/tools/qr-menu', $this->menuPayload())->json();
        $url = '/services/tools/qr-menu/'.$json['code'];

        $this->putJson($url, $this->menuPayload(['name' => 'Hacked']))->assertForbidden();
        $this->putJson($url, $this->menuPayload(['name' => 'Hacked']), ['X-Menu-Token' => 'wrong'])->assertForbidden();
        $this->getJson($url.'/data')->assertForbidden();

        $this->putJson($url, $this->menuPayload(['name' => 'Cafe Nile 2']), ['X-Menu-Token' => $json['token']])->assertOk();
        $this->getJson($url.'/data', ['X-Menu-Token' => $json['token']])->assertOk()->assertJsonPath('data.name', 'Cafe Nile 2');
        $this->get('/menu/'.$json['code'])->assertSee('Cafe Nile 2');
    }

    public function test_menu_creation_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/services/tools/qr-menu', $this->menuPayload())->assertCreated();
        }

        $this->postJson('/services/tools/qr-menu', $this->menuPayload())->assertStatus(429);
    }
}
