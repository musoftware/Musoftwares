<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsentType;
use App\Models\AdminSettings;
use App\Models\User;
use App\Services\Privacy\ConsentAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The old popup made "necessary only" wait for a bundled agreement (terms, privacy, storage outside Egypt),
 * and "I don't agree" blocked the site. Now the banner only asks about optional cookies, the site stays readable,
 * and only the choice the visitor really made is logged.
 */
class CookieBannerTest extends TestCase
{
    use RefreshDatabase;

    private const COOKIE = ConsentAuditService::TRACKING_COOKIE_NAME;

    public function test_new_visitor_reads_the_site_and_sees_three_equal_choices(): void
    {
        $html = $this->get('/?lang=ar')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<section id="cookie-banner"[^>]*class="(?![^"]*\bhidden\b)~', $html);
        $this->assertStringContainsString('data-cookie-action="necessary"', $html);
        $this->assertStringContainsString('data-cookie-action="all"', $html);
        $this->assertStringContainsString('data-cookie-action="customize"', $html);
        $this->assertStringNotContainsString('aria-modal', $html);
        $this->assertStringNotContainsString('pdpl-essential-checkbox', $html);
        $this->assertMatchesRegularExpression('~<input id="cookie-banner-tracking" type="checkbox"(?![^>]*checked)~', $html);
    }

    public function test_necessary_only_logs_only_that_choice(): void
    {
        $this->postJson('/privacy/consent/gate', ['cookies_tracking' => false])
            ->assertOk()
            ->assertJson(['ok' => true, 'cookiesTracking' => 'withdrawn'])
            ->assertCookie(self::COOKIE, 'withdrawn');

        $this->assertDatabaseHas('user_consents_log', ['consent_type' => ConsentType::CookiesTracking->value, 'action' => 'withdrawn']);
        $this->assertDatabaseMissing('user_consents_log', ['consent_type' => ConsentType::PrivacyPolicy->value]);
        $this->assertDatabaseMissing('user_consents_log', ['consent_type' => ConsentType::CrossBorderTransfer->value]);
    }

    public function test_accept_all_logs_granted(): void
    {
        $this->postJson('/privacy/consent/gate', ['cookies_tracking' => true])
            ->assertOk()
            ->assertCookie(self::COOKIE, 'granted');

        $this->assertDatabaseHas('user_consents_log', ['consent_type' => ConsentType::CookiesTracking->value, 'action' => 'granted']);
        $this->assertDatabaseCount('user_consents_log', 1);
    }

    public function test_choice_is_required_and_must_be_true_or_false(): void
    {
        $this->postJson('/privacy/consent/gate', [])->assertUnprocessable();
        $this->postJson('/privacy/consent/gate', ['cookies_tracking' => 'maybe'])->assertUnprocessable();
        $this->assertDatabaseCount('user_consents_log', 0);
    }

    public function test_later_visit_hides_the_banner_and_keeps_the_choice(): void
    {
        $html = $this->withCookie(self::COOKIE, 'withdrawn')->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~<section id="cookie-banner"[^>]*class="[^"]*\bhidden\b~', $html);
        $this->assertStringContainsString('var choice = "withdrawn"', $html);
    }

    public function test_trackers_start_only_after_accepting(): void
    {
        AdminSettings::SetValue('google_analytics_id', 'G-BANNER01');
        $loadOnStart = 'window.__musoftwareLoadTrackers({';

        $this->assertStringNotContainsString($loadOnStart, $this->get('/login')->getContent());
        $this->assertStringNotContainsString($loadOnStart, $this->withCookie(self::COOKIE, 'withdrawn')->get('/login')->getContent());
        $this->assertStringContainsString($loadOnStart, $this->withCookie(self::COOKIE, 'granted')->get('/login')->getContent());
    }

    public function test_changing_the_choice_logs_a_new_entry_for_a_signed_in_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/privacy/consent/gate', ['cookies_tracking' => true])->assertOk();
        $this->actingAs($user)->postJson('/privacy/consent/gate', ['cookies_tracking' => false])->assertOk();

        $state = app(ConsentAuditService::class)->currentStatesForUser($user->fresh());
        $this->assertFalse($state[ConsentType::CookiesTracking->value]['granted']);
        $this->assertDatabaseCount('user_consents_log', 2);
    }

    public function test_an_old_decline_cookie_no_longer_blocks_the_site(): void
    {
        $this->withCookie('pdpl_essential_declined', '1')->get('/')->assertOk();
        $this->withCookie('pdpl_essential_declined', '1')->get('/register')->assertOk();
    }

    public function test_footer_has_a_link_to_change_the_choice(): void
    {
        $this->get('/?lang=en')->assertOk()->assertSee("dispatchEvent(new CustomEvent('open-privacy-consent-gate'))", false);
    }
}
