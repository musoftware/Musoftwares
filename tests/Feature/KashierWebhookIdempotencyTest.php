<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Currency;
use App\Models\IncomingWebhook;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentProviderEvent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KashierWebhookIdempotencyTest extends TestCase
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

    public function test_duplicate_deposit_webhook_on_both_routes_credits_once(): void
    {
        $user = User::factory()->create(['currency_id' => $this->egp->id, 'user_balance' => 0]);
        $payload = $this->depositPayload($user, 'TX-DUP-1', 500);
        $headers = ['x-kashier-signature' => $this->sign($payload['data'])];

        $this->postJson('/financial/add-balance/webhook', $payload, $headers)->assertOk()->assertJson(['status' => 'success']);
        $this->postJson('/financial/add-balance/webhook', $payload, $headers)->assertOk()->assertJson(['message' => 'Already processed']);

        // Same event through the queued handler (sync queue in tests) must also be a no-op.
        $this->postJson('/api/webhooks/incoming/kashier', $payload, $headers)->assertStatus(202);

        $this->assertEquals(500.0, (float) $user->fresh()->user_balance);
        $this->assertSame(1, Transaction::where('user_id', $user->id)->where('type', 'received')->count());
        $this->assertSame(1, PaymentProviderEvent::where('provider', 'kashier')->where('external_id', 'TX-DUP-1')->count());
    }

    public function test_deposit_credits_the_signed_amount_not_the_metadata_original_amount(): void
    {
        $user = User::factory()->create(['currency_id' => $this->egp->id, 'user_balance' => 0]);
        $payload = $this->depositPayload($user, 'TX-TAMPER-1', 10, ['original_amount' => 99999, 'original_currency' => 'EGP']);

        $this->postJson('/financial/add-balance/webhook', $payload, ['x-kashier-signature' => $this->sign($payload['data'])])->assertOk();

        $this->assertEquals(10.0, (float) $user->fresh()->user_balance);
    }

    public function test_deposit_with_bad_signature_credits_nothing(): void
    {
        $user = User::factory()->create(['currency_id' => $this->egp->id, 'user_balance' => 0]);
        $payload = $this->depositPayload($user, 'TX-BAD-SIG', 500);

        $this->postJson('/financial/add-balance/webhook', $payload, ['x-kashier-signature' => 'forged'])->assertStatus(400);

        $this->assertEquals(0.0, (float) $user->fresh()->user_balance);
    }

    public function test_invoice_webhook_rejects_payment_below_amount_due(): void
    {
        [$user, $invoice] = $this->unpaidInvoice(500);
        $payload = $this->invoicePayload($user, $invoice, 'TX-INV-LOW', 100);

        $this->postJson('/billing/invoices/payment/webhook', $payload, ['x-kashier-signature' => $this->sign($payload['data'])])
            ->assertStatus(422);

        $this->assertNotEquals('paid', $invoice->fresh()->status);
    }

    public function test_invoice_webhook_marks_paid_when_signed_amount_covers_amount_due(): void
    {
        [$user, $invoice] = $this->unpaidInvoice(500);
        $payload = $this->invoicePayload($user, $invoice, 'TX-INV-FULL', 500);

        $this->postJson('/billing/invoices/payment/webhook', $payload, ['x-kashier-signature' => $this->sign($payload['data'])])
            ->assertOk();

        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_queued_job_rejects_sources_without_a_signature_verifier(): void
    {
        $webhook = IncomingWebhook::create([
            'source' => 'stripe',
            'event_type' => 'charge.succeeded',
            'payload' => ['type' => 'charge.succeeded'],
            'headers' => [],
            'status' => 'pending',
        ]);

        try {
            (new ProcessWebhookJob($webhook))->handle();
            $this->fail('Unverified webhook source was accepted.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Invalid webhook signature', $e->getMessage());
        }

        $this->assertSame('failed', $webhook->fresh()->status);
    }

    private function depositPayload(User $user, string $trxId, float $amount, array $extraMeta = []): array
    {
        return ['data' => $this->signedData([
            'merchantOrderId' => "deposit_abc-{$user->id}",
            'transactionId' => $trxId,
            'status' => 'SUCCESS',
            'amount' => $amount,
            'currency' => 'EGP',
        ], ['source' => 'balance-recharge', 'user_id' => $user->id] + $extraMeta)];
    }

    private function invoicePayload(User $user, Invoice $invoice, string $trxId, float $amount): array
    {
        return ['data' => $this->signedData([
            'merchantOrderId' => "u_inv_{$invoice->id}_abc-{$user->id}",
            'transactionId' => $trxId,
            'status' => 'SUCCESS',
            'amount' => $amount,
            'currency' => 'EGP',
        ], ['source' => 'user-invoice-payment', 'invoice_id' => $invoice->id, 'user_id' => $user->id])];
    }

    private function signedData(array $signed, array $metaData): array
    {
        return $signed + ['metaData' => $metaData, 'signatureKeys' => array_keys($signed)];
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

    /** @return array{0: User, 1: Invoice} */
    private function unpaidInvoice(float $total): array
    {
        $user = User::factory()->create(['currency_id' => $this->egp->id]);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $this->egp->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => $total,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Platform invoice',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => $total,
            'currency' => 'EGP',
        ]);

        return [$user, $invoice->fresh()];
    }
}
