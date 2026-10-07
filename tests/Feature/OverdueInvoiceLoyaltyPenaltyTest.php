<?php

namespace Tests\Feature;

use App\Events\LoyaltyTierDowngraded;
use App\Events\LoyaltyTierUpgraded;
use App\Mail\LoyaltyTierDowngradedMail;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\User;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OverdueInvoiceLoyaltyPenaltyTest extends TestCase
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
            ['name' => 'Bronze', 'min_lifetime_points' => 0, 'order_index' => 1, 'is_active' => true]
        );

        LoyaltyTier::firstOrCreate(
            ['slug' => 'silver'],
            ['name' => 'Silver', 'min_lifetime_points' => 500, 'order_index' => 2, 'is_active' => true]
        );

        $goldTier = LoyaltyTier::firstOrCreate(
            ['slug' => 'gold'],
            ['name' => 'Gold', 'min_lifetime_points' => 1000, 'order_index' => 3, 'is_active' => true]
        );

        LoyaltyRule::firstOrCreate(
            ['event_type' => 'invoice_overdue_penalty'],
            [
                'base_points' => 1,
                'conditions_payload' => [
                    'egp_per_point'          => 100,
                    'min_points_per_day'     => 1,
                    'affect_lifetime_points' => true,
                ],
                'is_active' => true,
            ]
        );

        $this->client = User::factory()->create([
            'email'                   => 'client_penalty@example.com',
            'currency_id'             => $this->currencyEgp->id,
            'loyalty_points_balance'  => 0,
            'loyalty_lifetime_points' => 0,
            'loyalty_tier_id'         => $goldTier->id,
            'tier'                    => 'gold',
        ]);

        LoyaltyPointTransaction::where('user_id', $this->client->id)->delete();
        $this->client->forceFill([
            'loyalty_points_balance'  => 1000,
            'loyalty_lifetime_points' => 1000,
            'loyalty_tier_id'         => $goldTier->id,
            'tier'                    => 'gold',
        ])->save();

        $this->loyaltyService = app(LoyaltyService::class);
    }

    private function createInvoiceWithItem(float $amount, string $jobStatus = 'done', string $status = 'unpaid', int $daysOffset = -2): Invoice
    {
        $dueDate = Carbon::now('Africa/Cairo')->addDays($daysOffset)->toDateString();

        $invoice = Invoice::create([
            'user_id'     => $this->client->id,
            'currency_id' => $this->currencyEgp->id,
            'currency'    => $this->currencyEgp->id,
            'status'      => $status,
            'job_status'  => $jobStatus,
            'due_date'    => $dueDate,
            'paid'        => $status === 'paid' ? $amount : 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Software Deliverable',
            'amount'     => $amount,
            'qty'        => 1,
        ]);

        $invoice->refresh();

        return $invoice;
    }

    public function test_overdue_done_invoice_deducts_points_daily_proportionally(): void
    {
        // 1,500 EGP overdue unpaid invoice marked as done (15 points penalty at 1 pt / 100 EGP)
        $invoice = $this->createInvoiceWithItem(amount: 1500, jobStatus: 'done', status: 'unpaid', daysOffset: -2);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->assertEquals(1, $result['penalized_invoices']);
        $this->assertEquals(15, $result['total_points_deducted']);

        $this->client->refresh();
        $this->assertEquals(985, $this->client->loyalty_points_balance);
        $this->assertEquals(985, $this->client->loyalty_lifetime_points);

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'user_id'         => $this->client->id,
            'event_type'      => 'invoice_overdue_penalty',
            'points'          => -15,
            'balance_after'   => 985,
            'reference_id'    => $invoice->id,
        ]);
    }

    public function test_penalty_records_actual_days_overdue(): void
    {
        $this->createInvoiceWithItem(amount: 1500, daysOffset: -5);

        $this->loyaltyService->deductOverdueInvoicePenalties();

        $txn = LoyaltyPointTransaction::where('event_type', 'invoice_overdue_penalty')->firstOrFail();
        $this->assertSame(5, $txn->metadata['days_overdue']);
    }

    public function test_penalty_deducts_from_both_balance_and_lifetime_points_and_can_demote_tier(): void
    {
        // Client starts with 1,000 points (Emerald tier requires 1,000 pts, Gold is 600 pts)
        $emeraldTier = LoyaltyTier::where('slug', 'emerald')->first();
        $this->client->forceFill([
            'loyalty_points_balance'  => 1000,
            'loyalty_lifetime_points' => 1000,
            'loyalty_tier_id'         => $emeraldTier?->id,
            'tier'                    => 'emerald',
        ])->save();

        // 5,000 EGP invoice => 50 points penalty => drops to 950 points => Demoted to Gold!
        $this->createInvoiceWithItem(amount: 5000, jobStatus: 'done', status: 'unpaid', daysOffset: -5);

        $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->client->refresh();
        $this->assertEquals(950, $this->client->loyalty_points_balance);
        $this->assertEquals(950, $this->client->loyalty_lifetime_points);
        $this->assertEquals('gold', $this->client->tier);
    }

    public function test_tier_downgrade_dispatches_event_and_sends_notification_email(): void
    {
        Mail::fake();

        $emeraldTier = LoyaltyTier::where('slug', 'emerald')->first();
        $this->client->forceFill([
            'loyalty_points_balance'  => 1000,
            'loyalty_lifetime_points' => 1000,
            'loyalty_tier_id'         => $emeraldTier?->id,
            'tier'                    => 'emerald',
        ])->save();

        $this->createInvoiceWithItem(amount: 5000, jobStatus: 'done', status: 'unpaid', daysOffset: -5);

        $this->loyaltyService->deductOverdueInvoicePenalties();

        Mail::assertQueued(LoyaltyTierDowngradedMail::class, function ($mail) {
            return $mail->hasTo($this->client->email);
        });

        $this->assertDatabaseHas('loyalty_notification_logs', [
            'user_id'           => $this->client->id,
            'notification_type' => 'tier_downgraded',
            'status'            => 'sent',
        ]);
    }

    public function test_tier_downgraded_mail_renders_html_properly(): void
    {
        $silverTier = LoyaltyTier::where('slug', 'silver')->first();
        $emeraldTier = LoyaltyTier::where('slug', 'emerald')->first();

        $mail = new LoyaltyTierDowngradedMail(
            $this->client,
            $silverTier,
            $emeraldTier,
            'Invoice settlement delay'
        );

        $html = $mail->render();

        $this->assertStringContainsString('Partnership Tier Update', $html);
        $this->assertStringContainsString($silverTier->name, $html);
        $this->assertStringContainsString('Invoice settlement delay', $html);
    }

    public function test_invoices_not_marked_done_are_not_penalized(): void
    {
        // Overdue unpaid, but job_status is processing (work not yet delivered)
        $this->createInvoiceWithItem(amount: 2000, jobStatus: 'processing', status: 'unpaid', daysOffset: -3);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->assertEquals(0, $result['penalized_invoices']);
        $this->assertEquals(0, $result['total_points_deducted']);

        $this->client->refresh();
        $this->assertEquals(1000, $this->client->loyalty_points_balance);
    }

    public function test_paid_invoices_are_not_penalized(): void
    {
        // Paid invoice marked as done
        $this->createInvoiceWithItem(amount: 2000, jobStatus: 'done', status: 'paid', daysOffset: -3);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->assertEquals(0, $result['penalized_invoices']);
        $this->assertEquals(0, $result['total_points_deducted']);

        $this->client->refresh();
        $this->assertEquals(1000, $this->client->loyalty_points_balance);
    }

    public function test_future_invoices_are_not_penalized(): void
    {
        // Done invoice whose due date is in the future
        $this->createInvoiceWithItem(amount: 2000, jobStatus: 'done', status: 'unpaid', daysOffset: 3);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->assertEquals(0, $result['penalized_invoices']);
        $this->assertEquals(0, $result['total_points_deducted']);

        $this->client->refresh();
        $this->assertEquals(1000, $this->client->loyalty_points_balance);
    }

    public function test_daily_idempotency_prevents_duplicate_deductions_on_same_day(): void
    {
        $this->createInvoiceWithItem(amount: 1000, jobStatus: 'done', status: 'unpaid', daysOffset: -1);

        // First run: deducts 10 points
        $result1 = $this->loyaltyService->deductOverdueInvoicePenalties();
        $this->assertEquals(10, $result1['total_points_deducted']);

        // Second run on the same day: idempotency catches it, 0 points deducted
        $result2 = $this->loyaltyService->deductOverdueInvoicePenalties();
        $this->assertEquals(0, $result2['total_points_deducted']);

        $this->client->refresh();
        $this->assertEquals(990, $this->client->loyalty_points_balance);
    }

    public function test_artisan_command_deduct_overdue_penalties_executes_successfully(): void
    {
        $this->createInvoiceWithItem(amount: 3000, jobStatus: 'done', status: 'unpaid', daysOffset: -2);

        $exitCode = $this->artisan('loyalty:deduct-overdue-penalties');
        $this->assertEquals(0, $exitCode);

        $this->client->refresh();
        $this->assertEquals(970, $this->client->loyalty_points_balance);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $this->createInvoiceWithItem(amount: 2000, jobStatus: 'done', status: 'unpaid', daysOffset: -2);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties(dryRun: true);

        $this->assertEquals(20, $result['total_points_deducted']);
        $this->assertTrue($result['dry_run']);

        $this->client->refresh();
        $this->assertEquals(1000, $this->client->loyalty_points_balance);
        $this->assertDatabaseMissing('loyalty_point_transactions', [
            'event_type' => 'invoice_overdue_penalty',
        ]);
    }

    public function test_invoice_without_exchange_rate_is_skipped_and_run_continues(): void
    {
        // EUR invoice with no EUR->EGP rate seeded: must be skipped, not abort the whole run.
        $eur = Currency::where('currency', 'EUR')->firstOrFail();
        $eurInvoice = $this->createInvoiceWithItem(amount: 1000, jobStatus: 'done', status: 'unpaid', daysOffset: -2);
        $eurInvoice->forceFill(['currency_id' => $eur->id, 'currency' => $eur->id])->saveQuietly();

        $this->createInvoiceWithItem(amount: 1500, jobStatus: 'done', status: 'unpaid', daysOffset: -2);

        $result = $this->loyaltyService->deductOverdueInvoicePenalties();

        $this->assertEquals(1, $result['penalized_invoices']);
        $this->assertEquals(15, $result['total_points_deducted']);
        $this->assertDatabaseMissing('loyalty_point_transactions', [
            'event_type'   => 'invoice_overdue_penalty',
            'reference_id' => $eurInvoice->id,
        ]);
    }
}
