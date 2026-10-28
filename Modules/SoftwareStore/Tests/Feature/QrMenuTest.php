<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\SoftwareStore\Models\FreeQrMenu;
use Modules\SoftwareStore\Services\QrMenuKit;
use Tests\TestCase;

/** The QR menu maker: photos, a demo restaurant, and menus kept in the maker's account. */
class QrMenuTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Lazeez',
            'currency' => 'EGP',
            'color' => '#c2410c',
            'lang' => 'ar',
            'theme' => 'dark',
            'cover' => 'lib:cover-plates',
            'sections' => [['title' => 'Grills', 'items' => [
                ['name' => 'Kebab', 'price' => 360, 'image' => 'lib:kebab', 'badges' => ['popular', 'spicy'], 'sold_out' => false],
                ['name' => 'Water', 'price' => 15],
            ]]],
        ];
    }

    public function test_the_demo_restaurant_is_a_finished_menu_in_both_languages(): void
    {
        $this->get('/menu/'.QrMenuKit::DEMO_CODE)->assertOk()
            ->assertSee('لذيذ')->assertSee('الأكثر طلبًا')->assertSee('420 ج.م')
            ->assertSee(asset('images/qr-menu/mixed-grill.webp'), false);
        $this->get('/menu/'.QrMenuKit::DEMO_CODE.'?lang=en')->assertOk()->assertSee('Mixed grill')->assertSee('420 EGP');

        foreach (QrMenuKit::LIBRARY as $name => $labels) {
            $this->assertFileExists(public_path("images/qr-menu/{$name}.webp"), $name);
        }
    }

    public function test_photos_labels_and_theme_are_shown(): void
    {
        $code = $this->postJson('/services/tools/qr-menu', $this->payload())->assertCreated()->json('code');

        $this->get('/menu/'.$code)->assertOk()
            ->assertSee(asset('images/qr-menu/kebab.webp'), false)
            ->assertSee(asset('images/qr-menu/cover-plates.webp'), false)
            ->assertSee('حار')
            ->assertSee('#0f0f10', false);
    }

    public function test_only_our_own_photo_references_are_accepted(): void
    {
        foreach (['https://evil.example/x.jpg', 'lib:not-a-photo', 'up:'.str_repeat('a', 32), 'javascript:alert(1)'] as $bad) {
            $this->postJson('/services/tools/qr-menu', $this->payload(['cover' => $bad]))->assertStatus(422);
        }
        $this->postJson('/services/tools/qr-menu', $this->payload(['sections' => [['title' => 'A', 'items' => [['name' => 'B', 'badges' => ['free-money']]]]]]))->assertStatus(422);
    }

    public function test_an_uploaded_photo_is_re_encoded_and_can_be_used(): void
    {
        Storage::fake('public_uploads');

        $ref = $this->post('/services/tools/qr-menu/image', ['image' => UploadedFile::fake()->image('dish.jpg', 2400, 1600)], ['Accept' => 'application/json'])
            ->assertCreated()->json('ref');

        $this->assertMatchesRegularExpression('/^up:[a-f0-9]{32}$/', $ref);
        $stored = Storage::disk('public_uploads')->get('qr-menus/'.substr($ref, 3).'.webp');
        $this->assertSame('RIFF', substr($stored, 0, 4));
        [$width] = getimagesizefromstring($stored);
        $this->assertSame(1400, $width);

        $this->postJson('/services/tools/qr-menu', $this->payload(['logo' => $ref]))->assertCreated();
        $this->post('/services/tools/qr-menu/image', ['image' => UploadedFile::fake()->create('menu.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_a_signed_in_maker_owns_the_menu_and_needs_no_link(): void
    {
        $owner = User::factory()->create();
        $json = $this->actingAs($owner)->postJson('/services/tools/qr-menu', $this->payload())->assertCreated()->json();

        $this->assertNull($json['token']);
        $this->assertSame($owner->id, FreeQrMenu::where('code', $json['code'])->value('user_id'));

        $this->actingAs($owner)->putJson('/services/tools/qr-menu/'.$json['code'], $this->payload(['name' => 'Lazeez 2']))->assertOk();
        $this->actingAs($owner)->getJson('/services/tools/qr-menu/mine')->assertOk()->assertJsonPath('0.name', 'Lazeez 2');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->putJson('/services/tools/qr-menu/'.$json['code'], $this->payload(['name' => 'Hacked']))->assertForbidden();
        $this->actingAs($stranger)->getJson('/services/tools/qr-menu/mine')->assertOk()->assertJsonCount(0);
    }

    public function test_a_guest_menu_joins_the_account_that_edits_it_with_the_link(): void
    {
        $json = $this->postJson('/services/tools/qr-menu', $this->payload())->json();
        $this->assertNotNull($json['token']);

        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/services/tools/qr-menu/'.$json['code'], $this->payload(), ['X-Menu-Token' => $json['token']])->assertOk();

        $this->assertSame($user->id, FreeQrMenu::where('code', $json['code'])->value('user_id'));
    }

    public function test_sign_in_returns_to_a_page_on_this_site_only(): void
    {
        $this->get('/login?redirect='.urlencode('/services/tools/qr-menu-maker?lang=ar'));
        $this->assertSame(url('/services/tools/qr-menu-maker?lang=ar'), session('url.intended'));

        session()->forget('url.intended');
        foreach (['https://evil.example', '//evil.example', '/\\evil.example'] as $bad) {
            $this->get('/login?redirect='.urlencode($bad));
            $this->assertNull(session('url.intended'), $bad);
        }
    }
}
