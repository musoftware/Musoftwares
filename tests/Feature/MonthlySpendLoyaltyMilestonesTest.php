<?php

namespace Tests\Feature;

use App\Events\InvoicePaid;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\User;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlySpendLoyaltyMilestonesTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currencyEgp;
    protected User $client;
    protected LoyaltyService $loyaltyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyEgp = Currency::firstOrCreate(
            ['currency' => 'EGP'],
            ['name' => 'Egyptian Pound', 'symbol' => 'EGP']
        );

        LoyaltyTier::firstOrCreate(
            ['slug' => 'bronze'],
            ['name' => 'Bronze', 'min_lifetime_points' => 0, 'order_index' => 1]
        );

        LoyaltyRule::firstOrCreate(
            ['event_type' => 'invoice_payment'],
            ['base_points' => 200, 'is_active' => true]
        );

        LoyaltyRule::firstOrCreate(
            ['event_type' => 'monthly_spend_10k'],
            [
                'base_points' => 600,
                'conditions_payload' => ['threshold_egp' => 10000],
                'is_active' => true,
            ]
        );

        LoyaltyRule::firstOrCreate(
            ['event_type' => 'monthly_spend_15k'],
            [
                'base_points' => 1000,
                'conditions_payload' => ['threshold_egp' => 15000],
                'is_active' => true,
            ]
        );

        $this->client = User::factory()->create([
            'currency_id' => $this->currencyEgp->id,
            'loyalty_points_balance' => 0,
            'loyalty_lifetime_points' => 0,
        ]);
        LoyaltyPointTransaction::where('user_id', $this->client->id)->delete();
        $this->client->forceFill(['loyalty_points_balance' => 0, 'loyalty_lifetime_points' => 0])->save();

        $this->loyaltyService = app(LoyaltyService::class);
    }

    private function payInvoice(float $amount, ?Carbon $paidAt = null): Invoice
    {
        $paidDate = $paidAt ?? Carbon::now('Africa/Cairo');

        $invoice = Invoice::create([
            'user_id' => $this->client->id,
            'currency' => $this->currencyEgp->id,
            'currency_id' => $this->currencyEgp->id,
            'paid' => $amount,
            'unpaid' => 0,
            'status' => 'paid',
            'paid_at' => $paidDate,
        ]);

        event(new InvoicePaid($invoice));

        return $invoice;
    }

    public function test_invoice_below_10k_does_not_trigger_milestone_bonus(): void
    {
        $this->payInvoice(5000.0);

        $this->assertDatabaseMissing('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_10k',
        ]);

        $this->assertDatabaseMissing('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_15k',
        ]);

        // Only normal invoice payment points (200)
        $this->assertEquals(200, $this->client->fresh()->loyalty_points_balance);
    }

    public function test_reaching_10k_monthly_spend_awards_600_bonus_points(): void
    {
        $this->payInvoice(10000.0);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_10k',
            'points' => 600,
        ]);

        $this->assertDatabaseMissing('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_15k',
        ]);

        // 200 base invoice points + 600 milestone bonus = 800
        $this->assertEquals(800, $this->client->fresh()->loyalty_points_balance);
    }

    public function test_reaching_15k_monthly_spend_awards_additional_1000_bonus_points(): void
    {
        // First invoice: 10,000 EGP -> awards 200 base + 600 milestone
        $this->payInvoice(10000.0);
        $this->assertEquals(800, $this->client->fresh()->loyalty_points_balance);

        // Second invoice in same month: 5,000 EGP (Total reaches 15,000) -> awards 200 base + 1,000 milestone
        $this->payInvoice(5000.0);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_10k',
            'points' => 600,
        ]);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_15k',
            'points' => 1000,
        ]);

        // Total points = 200 + 600 + 200 + 1000 = 2000
        $this->assertEquals(2000, $this->client->fresh()->loyalty_points_balance);
    }

    public function test_single_invoice_of_15k_awards_both_milestones_together(): void
    {
        $this->payInvoice(15000.0);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_10k',
            'points' => 600,
        ]);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id' => $this->client->id,
            'event_type' => 'monthly_spend_15k',
            'points' => 1000,
        ]);

        // 200 base + 600 + 1000 = 1800
        $this->assertEquals(1800, $this->client->fresh()->loyalty_points_balance);
    }

    public function test_milestones_are_idempotent_within_the_same_calendar_month(): void
    {
        $this->payInvoice(15000.0);

        $count10k = LoyaltyPointTransaction::where('user_id', $this->client->id)
            ->where('event_type', 'monthly_spend_10k')
            ->count();
        $count15k = LoyaltyPointTransaction::where('user_id', $this->client->id)
            ->where('event_type', 'monthly_spend_15k')
            ->count();

        $this->assertEquals(1, $count10k);
        $this->assertEquals(1, $count15k);

        // Third invoice in the same month
        $this->payInvoice(8000.0);

        $count10kAfter = LoyaltyPointTransaction::where('user_id', $this->client->id)
            ->where('event_type', 'monthly_spend_10k')
            ->count();
        $count15kAfter = LoyaltyPointTransaction::where('user_id', $this->client->id)
            ->where('event_type', 'monthly_spend_15k')
            ->count();

        $this->assertEquals(1, $count10kAfter);
        $this->assertEquals(1, $count15kAfter);

        // Balance increased only by regular invoice points (200), not milestones
        $this->assertEquals(2000, $this->client->fresh()->loyalty_points_balance);
    }

    public function test_new_month_resets_milestones(): void
    {
        $lastMonth = Carbon::now('Africa/Cairo')->subMonthNoOverflow();
        $this->payInvoice(15000.0, $lastMonth);

        $this->assertEquals(1800, $this->client->fresh()->loyalty_points_balance);

        // Current month: client spends 10,000 again
        $currentMonth = Carbon::now('Africa/Cairo');
        $this->payInvoice(10000.0, $currentMonth);

        $count10k = LoyaltyPointTransaction::where('user_id', $this->client->id)
            ->where('event_type', 'monthly_spend_10k')
            ->count();

        // 1 for last month, 1 for current month
        $this->assertEquals(2, $count10k);

        // 1800 from last month + 200 base + 600 milestone = 2600
        $this->assertEquals(2600, $this->client->fresh()->loyalty_points_balance);
    }
}
