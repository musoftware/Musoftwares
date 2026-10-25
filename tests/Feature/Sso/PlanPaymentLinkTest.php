<?php

namespace Tests\Feature\Sso;

use App\Http\Controllers\Auth\SetPasswordController;
use App\Jobs\ProcessWebhookJob;
use App\Mail\PlanAccountCreatedMail;
use App\Models\Currency;
use App\Models\IncomingWebhook;
use App\Models\PaymentLink;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PlanPaymentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * GoldSaver Pro bought through a monolith payment link, with or without an account.
 */
class PlanPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'plan-link-secret';

    private const PLAN = 'gold-saver-pro-monthly';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.goldsaversys.shared_secret' => self::SECRET,
            'services.goldsaversys.url' => 'https://gold.example.test',
            'saas.payment_link_owner_email' => 'owner@example.test',
        ]);
        Currency::create(['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%s']);
        User::factory()->create(['email' => 'owner@example.test']);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function postSigned(array $body, string $system = 'goldsaversys', ?string $secret = null): TestResponse
    {
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.payment-links-create', $secret ?? self::SECRET);

        return $this->postJson('/api/sso/payment-links', $body, [
            'X-GoldSaver-System' => $system,
            'X-GoldSaver-Timestamp' => $timestamp,
            'X-GoldSaver-Signature' => $signature,
        ]);
    }

    private function payViaWebhook(PaymentLink $link, string $trx = 'trx_plan_1'): void
    {
        $job = new ProcessWebhookJob(new IncomingWebhook(['source' => 'kashier', 'payload' => [], 'headers' => []]));
        $method = new \ReflectionMethod($job, 'handlePaymentLink');
        $method->setAccessible(true);
        $method->invoke($job, $trx, (float) $link->amount, ['payment_link_id' => $link->id]);
    }

    private function goldSubscription(User $user): ?UserSubscription
    {
        return UserSubscription::where('user_id', $user->id)->where('object', 'gold-saver')->first();
    }

    public function test_unsigned_or_badly_signed_requests_are_rejected(): void
    {
        $this->postJson('/api/sso/payment-links', ['plan' => self::PLAN])->assertStatus(401);
        $this->postSigned(['plan' => self::PLAN], 'goldsaversys', 'wrong-secret')->assertStatus(401);
        $this->assertSame(0, PaymentLink::count());
    }

    public function test_unknown_plan_or_plan_of_another_system_is_rejected(): void
    {
        config(['services.bookingsys.shared_secret' => self::SECRET]);

        $this->postSigned(['plan' => 'does-not-exist'])->assertStatus(422)->assertJson(['error' => 'unknown_plan']);
        $this->postSigned(['plan' => self::PLAN], 'bookingsys')->assertStatus(422);
    }

    public function test_creates_a_guest_link_with_the_monolith_price(): void
    {
        config(['saas.external_plans.'.self::PLAN.'.amount' => 1.0]);

        $response = $this->postSigned([
            'plan' => self::PLAN,
            'return_url' => 'https://gold.example.test/billing/success',
        ])->assertCreated();

        $link = PaymentLink::sole();
        $this->assertSame('1.00', $link->amount);
        $this->assertNull($link->client_id);
        $this->assertSame('gold-saver', $link->metadata['plan']['object']);
        $this->assertSame('https://gold.example.test/billing/success', $link->metadata['return_url']);
        $this->assertTrue($link->expires_at->isFuture());
        $response->assertJson(['url' => route('guest.payment-links.show', ['uuid' => $link->uuid]), 'currency' => 'USD']);
    }

    public function test_return_url_on_a_foreign_host_is_dropped(): void
    {
        $this->postSigned(['plan' => self::PLAN, 'return_url' => 'https://evil.example.com/steal'])->assertCreated();

        $this->assertNull(PaymentLink::sole()->metadata['return_url']);
    }

    public function test_paying_a_signed_in_users_link_activates_their_subscription(): void
    {
        $user = User::factory()->create();
        $this->postSigned(['plan' => self::PLAN, 'user_id' => $user->id])->assertCreated();
        $link = PaymentLink::sole();
        $this->assertSame($user->id, $link->client_id);

        $this->payViaWebhook($link);

        $sub = $this->goldSubscription($user);
        $this->assertNotNull($sub);
        $this->assertSame('active', $sub->status);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $sub->expires_at->timestamp, 5);
    }

    public function test_guest_payment_opens_an_account_and_emails_a_set_password_link(): void
    {
        Mail::fake();
        $this->postSigned(['plan' => self::PLAN])->assertCreated();
        $link = PaymentLink::sole();
        $link->forceFill(['metadata' => $link->metadata + ['payer' => ['name' => 'Mona', 'email' => 'Mona@Example.test']]])->save();

        $this->payViaWebhook($link);

        $user = User::where('email', 'mona@example.test')->sole();
        $this->assertSame($user->id, $link->fresh()->client_id);
        $this->assertSame('active', $this->goldSubscription($user)->status);
        Mail::assertSent(PlanAccountCreatedMail::class, fn ($mail) => $mail->hasTo('mona@example.test')
            && str_contains($mail->setLink, 'next=%2Fsso%2Fgoldsaversys'));
    }

    public function test_guest_payment_with_an_existing_email_extends_that_account(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'saver@example.test']);
        UserSubscription::create([
            'user_id' => $user->id, 'object' => 'gold-saver', 'status' => 'active',
            'started_at' => now()->subDays(10), 'expires_at' => now()->addDays(20),
        ]);
        $this->postSigned(['plan' => self::PLAN])->assertCreated();
        $link = PaymentLink::sole();
        $link->forceFill(['metadata' => $link->metadata + ['payer' => ['name' => 'X', 'email' => 'saver@example.test']]])->save();

        $this->payViaWebhook($link);

        $this->assertEqualsWithDelta(now()->addDays(50)->timestamp, $this->goldSubscription($user)->expires_at->timestamp, 5);
        Mail::assertNothingSent();
        // Owner + the existing saver: no duplicate account was opened.
        $this->assertSame(2, User::count());
    }

    public function test_duplicate_webhooks_extend_the_subscription_only_once(): void
    {
        $user = User::factory()->create();
        $this->postSigned(['plan' => self::PLAN, 'user_id' => $user->id])->assertCreated();
        $link = PaymentLink::sole();

        $this->payViaWebhook($link);
        $this->payViaWebhook($link);

        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $this->goldSubscription($user)->expires_at->timestamp, 5);
    }

    public function test_ordinary_payment_links_do_not_create_subscriptions(): void
    {
        $owner = User::where('email', 'owner@example.test')->sole();
        $client = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $owner->id, 'client_id' => $client->id, 'amount' => 50]);

        $this->payViaWebhook($link);

        $this->assertSame(PaymentLink::STATUS_PAID, $link->fresh()->status);
        $this->assertSame(0, UserSubscription::count());
    }

    public function test_admin_manual_mark_paid_also_activates_the_plan(): void
    {
        $user = User::factory()->create();
        $link = app(PlanPaymentLinkService::class)->create(app(PlanPaymentLinkService::class)->plan(self::PLAN), $user, null);

        $link->markPaid(PaymentLink::METHOD_MANUAL, null, 1);
        app(PlanPaymentLinkService::class)->fulfil($link);

        $this->assertSame('active', $this->goldSubscription($user)->status);
    }

    public function test_set_password_link_with_next_signs_in_verifies_and_redirects(): void
    {
        $user = User::factory()->unverified()->create();
        $link = SetPasswordController::issueLink($user, null, '/sso/goldsaversys');

        $this->post($link, ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
            ->assertRedirect('/sso/goldsaversys');

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_set_password_link_without_next_keeps_the_old_behaviour(): void
    {
        $user = User::factory()->unverified()->create();
        $link = SetPasswordController::issueLink($user, 1);

        $this->post($link, ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
            ->assertRedirect(route('login'));

        $this->assertFalse(Auth::check());
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_tampering_with_next_breaks_the_signature(): void
    {
        $user = User::factory()->create();
        $link = SetPasswordController::issueLink($user, null, '/sso/goldsaversys');
        $tampered = str_replace('next=%2Fsso%2Fgoldsaversys', 'next=%2Fadmin', $link);

        $this->post($tampered, ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
            ->assertForbidden();
    }
}
