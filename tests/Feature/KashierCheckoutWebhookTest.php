<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\KashierCheckout;
use App\Models\PaymentProviderEvent;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KashierCheckoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test_secret_key';

    private Currency $egp;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.kashier.secret_key' => self::SECRET]);
        $this->egp = Currency::where('currency', 'EGP')->firstOrFail();
    }

    public function test_points_checkout_redirect_records_purchase_server_side(): void
    {
        $user = $this->egpUser();

        $this->actingAs($user)->post(route('point-purchases.store-wallet'), ['points' => 100]);

        $checkout = KashierCheckout::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(KashierCheckout::PURPOSE_POINTS, $checkout->purpose);
        $this->assertSame(100, $checkout->payload['points']);
        $this->assertSame(KashierCheckout::STATUS_PENDING, $checkout->status);
    }

    public function test_points_webhook_uses_checkout_points_not_tampered_metadata_and_dedups(): void
    {
        $user = $this->egpUser();
        $checkout = KashierCheckout::open($user, KashierCheckout::PURPOSE_POINTS, 100.0, $this->egp->id, ['points' => 100, 'package_id' => null]);
        $payload = $this->payload($user, $checkout, 'TX-PTS-1', 100, ['points' => 999999]);
        $headers = ['x-kashier-signature' => $this->sign($payload['data'])];

        $this->postJson(route('points.kashier.webhook'), $payload, $headers)->assertOk()->assertJson(['status' => 'success']);
        $this->postJson(route('points.kashier.webhook'), $payload, $headers)->assertOk()->assertJson(['message' => 'Already processed']);

        $this->assertSame(100, (int) $user->fresh()->points_balance);
        $this->assertSame(KashierCheckout::STATUS_COMPLETED, $checkout->fresh()->status);
        $this->assertSame(1, PaymentProviderEvent::where('external_id', 'TX-PTS-1')->count());
    }

    public function test_points_webhook_rejects_payment_below_checkout_price(): void
    {
        $user = $this->egpUser();
        $checkout = KashierCheckout::open($user, KashierCheckout::PURPOSE_POINTS, 500.0, $this->egp->id, ['points' => 500, 'package_id' => null]);
        $payload = $this->payload($user, $checkout, 'TX-PTS-LOW', 5);

        $this->postJson(route('points.kashier.webhook'), $payload, ['x-kashier-signature' => $this->sign($payload['data'])])
            ->assertStatus(422);

        $this->assertSame(0, (int) $user->fresh()->points_balance);
        $this->assertSame(KashierCheckout::STATUS_PENDING, $checkout->fresh()->status);
    }

    public function test_subscription_webhook_activates_checkout_items_not_tampered_metadata_and_dedups(): void
    {
        $user = $this->egpUser();
        $checkout = KashierCheckout::open($user, KashierCheckout::PURPOSE_SUBSCRIPTION, 300.0, $this->egp->id, [
            'billing_cycle' => '1_month', 'days' => 30, 'items' => ['erp'], 'is_new_system' => false,
        ]);
        $payload = $this->payload($user, $checkout, 'TX-SUB-1', 300, ['items' => ['erp', 'crm', 'hr'], 'days' => 3650]);
        $headers = ['x-kashier-signature' => $this->sign($payload['data'])];

        $this->postJson(route('subscriptions.kashier.webhook'), $payload, $headers)->assertOk();
        $this->postJson(route('subscriptions.kashier.webhook'), $payload, $headers)->assertOk()->assertJson(['message' => 'Already processed']);

        $subscriptions = UserSubscription::where('user_id', $user->id)->get();
        $this->assertSame(['erp'], $subscriptions->pluck('object')->all());
        $this->assertTrue($subscriptions->first()->expires_at->lessThan(now()->addDays(31)));
    }

    public function test_webhook_cannot_fulfil_another_users_checkout(): void
    {
        $owner = $this->egpUser();
        $attacker = $this->egpUser();
        $checkout = KashierCheckout::open($owner, KashierCheckout::PURPOSE_POINTS, 100.0, $this->egp->id, ['points' => 100, 'package_id' => null]);
        $payload = $this->payload($attacker, $checkout, 'TX-PTS-SWAP', 100);

        $this->postJson(route('points.kashier.webhook'), $payload, ['x-kashier-signature' => $this->sign($payload['data'])])
            ->assertStatus(422);

        $this->assertSame(KashierCheckout::STATUS_PENDING, $checkout->fresh()->status);
    }

    private function egpUser(): User
    {
        return User::factory()->create(['currency_id' => $this->egp->id, 'user_balance' => 0]);
    }

    private function payload(User $payer, KashierCheckout $checkout, string $trxId, float $amount, array $extraMeta = []): array
    {
        $signed = [
            'merchantOrderId' => "pts_abc-{$payer->id}",
            'transactionId' => $trxId,
            'status' => 'SUCCESS',
            'amount' => $amount,
            'currency' => 'EGP',
        ];

        return ['data' => $signed + [
            'metaData' => ['checkout_id' => $checkout->id, 'user_id' => $payer->id, 'source' => $checkout->purpose] + $extraMeta,
            'signatureKeys' => array_keys($signed),
        ]];
    }

    /** Mirrors KashierHelper::validatePayload(): HMAC of the sorted signed keys. */
    private function sign(array $data): string
    {
        $keys = $data['signatureKeys'];
        sort($keys);
        $signed = [];
        foreach ($keys as $key) {
            $signed[$key] = $data[$key];
        }

        return hash_hmac('sha256', http_build_query($signed, '', '&', PHP_QUERY_RFC3986), self::SECRET);
    }
}
