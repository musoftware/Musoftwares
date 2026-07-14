<?php

namespace Tests\Unit\Models;

use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_isExpired_returns_true_when_past(): void
    {
        $link = PaymentLink::factory()->expired()->create();

        $this->assertTrue($link->isExpired());
    }

    public function test_isExpired_returns_false_when_future(): void
    {
        $link = PaymentLink::factory()->create(['expires_at' => now()->addDay()]);

        $this->assertFalse($link->isExpired());
    }

    public function test_mark_cancelled_only_works_when_pending(): void
    {
        $link = PaymentLink::factory()->create(['status' => PaymentLink::STATUS_PENDING]);
        $this->assertTrue($link->markCancelled());
        $this->assertSame(PaymentLink::STATUS_CANCELLED, $link->fresh()->status);

        $paid = PaymentLink::factory()->paid()->create();
        $this->assertFalse($paid->markCancelled());
        $this->assertSame(PaymentLink::STATUS_PAID, $paid->fresh()->status);
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $user = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($link->markPaid(PaymentLink::METHOD_KASHIER, 'trx_abc'));
        $this->assertFalse($link->fresh()->markPaid(PaymentLink::METHOD_KASHIER, 'trx_def'));
        $this->assertSame('trx_abc', $link->fresh()->paid_transaction_id);
    }

    public function test_scope_for_user_matches_owner_or_client(): void
    {
        $owner = User::factory()->create();
        $client = User::factory()->create();
        $other = User::factory()->create();

        PaymentLink::factory()->create(['user_id' => $owner->id, 'client_id' => $client->id]);
        PaymentLink::factory()->create(['user_id' => $other->id]);

        $this->assertSame(1, PaymentLink::forUser($owner->id)->count());
        $this->assertSame(1, PaymentLink::forUser($client->id)->count());
        $this->assertSame(0, PaymentLink::forUser(99999)->count());
    }
}