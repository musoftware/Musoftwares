<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EgyptArabicOnlyTest extends TestCase
{
    use RefreshDatabase;

    private const EGYPT_IP = '41.32.0.1';

    private const GERMANY_IP = '5.9.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        // storage/app/geoip.mmdb is not in git, so the country data is replaced here.
        $this->app->instance(IpGeolocationService::class, new class extends IpGeolocationService
        {
            public function getCountryFromIp($ip)
            {
                return $ip === EgyptArabicOnlyTest::egyptIp() ? 'EG' : 'DE';
            }
        });
    }

    public static function egyptIp(): string
    {
        return self::EGYPT_IP;
    }

    private function visit(string $ip, string $url): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->get($url);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function article(array $attributes): BlogArticle
    {
        return BlogArticle::create($attributes + [
            'group_id' => 'g-1',
            'content' => '<p>Body</p>',
            'excerpt' => 'Short.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_a_guest_in_egypt_gets_arabic_even_when_asking_for_english(): void
    {
        $this->visit(self::EGYPT_IP, '/')->assertOk()->assertSee('<html lang="ar"', false);
        $this->visit(self::EGYPT_IP, '/?lang=en')->assertOk()->assertSee('<html lang="ar"', false);
    }

    public function test_a_client_in_egypt_gets_arabic_and_keeps_the_language_saved_on_the_account(): void
    {
        $client = User::factory()->create(['locale' => 'en', 'lang' => 'en']);
        $this->actingAs($client);

        $this->visit(self::EGYPT_IP, '/?lang=en')->assertOk()->assertSee('<html lang="ar"', false);

        $this->assertSame('en', $client->fresh()->locale);
        $this->assertSame('en', $client->fresh()->lang);
    }

    public function test_an_admin_in_egypt_can_still_choose_english(): void
    {
        $this->actingAs($this->admin());

        $this->visit(self::EGYPT_IP, '/?lang=en')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_a_visitor_outside_egypt_can_still_choose_the_language(): void
    {
        $this->visit(self::GERMANY_IP, '/?lang=en')->assertOk()->assertSee('<html lang="en"', false);
        $this->visit(self::GERMANY_IP, '/?lang=ar')->assertOk()->assertSee('<html lang="ar"', false);
    }

    public function test_the_language_controls_are_hidden_only_for_locked_visitors(): void
    {
        $this->visit(self::EGYPT_IP, '/estimator')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'ar')->where('localeLocked', true));

        $this->visit(self::GERMANY_IP, '/estimator')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('localeLocked', false));

        $this->actingAs($this->admin());
        $this->visit(self::EGYPT_IP, '/estimator')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('localeLocked', false));
    }

    public function test_an_english_article_sends_egypt_visitors_to_its_arabic_version(): void
    {
        $this->article(['language' => 'ar', 'title' => 'التكلفة', 'slug' => 'website-cost']);
        $this->article(['language' => 'en', 'title' => 'Cost', 'slug' => 'website-cost-en']);

        $this->visit(self::EGYPT_IP, '/blog/website-cost-en')->assertRedirect(url('/blog/website-cost'));
        $this->visit(self::GERMANY_IP, '/blog/website-cost-en')->assertOk();
    }
}
