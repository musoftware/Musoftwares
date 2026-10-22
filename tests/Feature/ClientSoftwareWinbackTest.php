<?php

namespace Tests\Feature;

use App\Mail\ClientSoftwareWinbackMail;
use App\Models\ClientSoftwareWinbackLog;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ClientSoftwareWinbackService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientSoftwareWinbackTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;
    protected ClientSoftwareWinbackService $winbackService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );

        $this->winbackService = app(ClientSoftwareWinbackService::class);
    }

    public function test_inactive_client_with_past_paid_invoice_gets_first_reminder(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'inactive_client@example.com',
            'last_activity_at'       => Carbon::now()->subDays(200),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 1500,
            'unpaid'     => 0,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $this->assertEquals(1, $this->winbackService->determineNextReminderNumber($user));

        $stats = $this->winbackService->processAllEligible();

        $this->assertEquals(1, $stats['sent']);

        Mail::assertQueued(ClientSoftwareWinbackMail::class, function ($mail) use ($user) {
            return $mail->hasTo('inactive_client@example.com')
                && $mail->reminderNumber === 1
                && $mail->couponCode === ClientSoftwareWinbackService::COUPON_CODE;
        });

        $this->assertDatabaseHas('client_software_winback_logs', [
            'user_id'         => $user->id,
            'reminder_number' => 1,
            'coupon_code'     => ClientSoftwareWinbackService::COUPON_CODE,
        ]);

        // Verifies coupon was created
        $this->assertDatabaseHas('coupons', [
            'code' => ClientSoftwareWinbackService::COUPON_CODE,
            'discount_percentage' => 50.00,
        ]);
    }

    public function test_recent_active_client_is_excluded(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'recent_client@example.com',
            'last_activity_at'       => Carbon::now()->subDays(30),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 2000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(30),
            'updated_at' => Carbon::now()->subDays(30),
        ]);

        $this->assertNull($this->winbackService->determineNextReminderNumber($user));

        $stats = $this->winbackService->processAllEligible();
        $this->assertEquals(0, $stats['sent']);
        Mail::assertNotQueued(ClientSoftwareWinbackMail::class);
    }

    public function test_unsubscribed_user_is_suppressed(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'unsubscribed@example.com',
            'last_activity_at'       => Carbon::now()->subDays(200),
            'winback_unsubscribed_at'=> Carbon::now()->subDays(10),
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 500,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $this->assertTrue($this->winbackService->shouldSuppress($user));
        $this->assertNull($this->winbackService->determineNextReminderNumber($user));
    }

    public function test_user_with_open_ticket_is_suppressed(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'openticket@example.com',
            'last_activity_at'       => Carbon::now()->subDays(200),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        Ticket::create([
            'user_id'        => $user->id,
            'ticket_subject' => 'Issue with hosting',
            'ticket_message' => 'Please investigate this problem.',
            'ticket_status'  => 'pending',
            'ticket_type'    => 'problem',
        ]);

        $this->assertTrue($this->winbackService->shouldSuppress($user));
        $this->assertNull($this->winbackService->determineNextReminderNumber($user));
    }

    public function test_recurring_cadence_waits_90_days_before_reminder_two(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'cadence_client@example.com',
            'last_activity_at'       => Carbon::now()->subDays(250),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(260),
            'updated_at' => Carbon::now()->subDays(260),
        ]);

        // Reminder 1 was sent only 30 days ago (< 90 days cadence)
        ClientSoftwareWinbackLog::create([
            'user_id'         => $user->id,
            'reminder_number' => 1,
            'coupon_code'     => ClientSoftwareWinbackService::COUPON_CODE,
            'sent_at'         => Carbon::now()->subDays(30),
        ]);

        $this->assertNull($this->winbackService->determineNextReminderNumber($user));

        // Now advance sent_at to 95 days ago (>= 90 days cadence)
        ClientSoftwareWinbackLog::where('user_id', $user->id)->update([
            'sent_at' => Carbon::now()->subDays(95),
        ]);

        $this->assertEquals(2, $this->winbackService->determineNextReminderNumber($user));

        $stats = $this->winbackService->processAllEligible();
        $this->assertEquals(1, $stats['sent']);

        Mail::assertQueued(ClientSoftwareWinbackMail::class, function ($mail) {
            return $mail->reminderNumber === 2;
        });

        $this->assertDatabaseHas('client_software_winback_logs', [
            'user_id'         => $user->id,
            'reminder_number' => 2,
        ]);
    }

    public function test_stops_after_max_four_reminders(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'capped_client@example.com',
            'last_activity_at'       => Carbon::now()->subDays(600),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(610),
            'updated_at' => Carbon::now()->subDays(610),
        ]);

        for ($i = 1; $i <= 4; $i++) {
            ClientSoftwareWinbackLog::create([
                'user_id'         => $user->id,
                'reminder_number' => $i,
                'coupon_code'     => ClientSoftwareWinbackService::COUPON_CODE,
                'sent_at'         => Carbon::now()->subDays(400 - ($i * 95)),
            ]);
        }

        $this->assertNull($this->winbackService->determineNextReminderNumber($user));
        $this->assertEquals(0, $this->winbackService->processAllEligible()['sent']);
    }

    public function test_artisan_command_with_dry_run(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email'                  => 'dryrun@example.com',
            'last_activity_at'       => Carbon::now()->subDays(200),
            'winback_unsubscribed_at'=> null,
        ]);

        Invoice::create([
            'user_id'    => $user->id,
            'paid'       => 1000,
            'status'     => 'paid',
            'currency'   => $this->currency->id,
            'created_at' => Carbon::now()->subDays(210),
            'updated_at' => Carbon::now()->subDays(210),
        ]);

        $exitCode = $this->artisan('winback:software-clients', ['--dry-run' => true]);
        $this->assertEquals(0, $exitCode);

        // In dry run, no mail should be queued and no log persisted
        Mail::assertNotQueued(ClientSoftwareWinbackMail::class);
        $this->assertDatabaseMissing('client_software_winback_logs', [
            'user_id' => $user->id,
        ]);
    }

    public function test_touch_activity_marks_logs_as_recovered_when_client_returns(): void
    {
        $user = User::factory()->create([
            'email'                  => 'returning_client@example.com',
            'last_activity_at'       => Carbon::now()->subDays(200),
            'winback_unsubscribed_at'=> null,
        ]);

        ClientSoftwareWinbackLog::create([
            'user_id'         => $user->id,
            'reminder_number' => 1,
            'status'          => 'sent',
            'coupon_code'     => ClientSoftwareWinbackService::COUPON_CODE,
            'sent_at'         => Carbon::now()->subDays(10),
        ]);

        // User performs activity (logs in or pays invoice)
        app(\App\Services\WinbackService::class)->touchActivity($user, 'login');

        $this->assertDatabaseHas('client_software_winback_logs', [
            'user_id'             => $user->id,
            'reminder_number'     => 1,
            'status'              => 'recovered',
            'recovery_event_type' => 'login',
        ]);

        $this->assertNotNull($user->fresh()->last_activity_at);
        $this->assertEquals('login', $user->fresh()->last_activity_type);
    }
}
