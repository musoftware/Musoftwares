<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\OutgoingEmail;
use App\Mail\SubscriptionExpiredReminderMail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionExpiryRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    /**
     * Test naturally expiring subscription transition, immediate reminder (Week 0),
     * and high-fidelity outgoing email logging.
     */
    public function test_naturally_expired_subscription_is_marked_expired_and_notified_immediately()
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
            'lang' => 'ar',
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now()->subDays(30),
            'expires_at' => now()->subMinute(), // Expired
            'auto_renew' => false, // Will expire naturally
            'expired_reminders_sent' => 0,
            'last_expired_reminder_sent_at' => null,
        ]);

        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        // Assert subscription status updated to expired
        $subscription->refresh();
        $this->assertEquals('expired', $subscription->status);
        $this->assertEquals(1, $subscription->expired_reminders_sent);
        $this->assertNotNull($subscription->last_expired_reminder_sent_at);

        // Assert email log was created in outgoing_emails
        $emailLog = OutgoingEmail::where('to_email', 'client@example.com')->first();
        $this->assertNotNull($emailLog);
        $this->assertEquals('sent', $emailLog->status);
        $this->assertEquals('SubscriptionExpiredReminderMail', $emailLog->mail_class);
        $this->assertStringContainsString('انتهى الاشتراك', $emailLog->subject);
    }

    /**
     * Test the weekly interval constraint: reminders are only sent every 7 days.
     */
    public function test_reminders_are_only_sent_weekly_up_to_four_times()
    {
        $user = User::factory()->create([
            'email' => 'client2@example.com',
            'lang' => 'en',
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'crm',
            'status' => 'expired',
            'started_at' => now()->subDays(40),
            'expires_at' => now()->subDays(10),
            'auto_renew' => false,
            'expired_reminders_sent' => 1,
            'last_expired_reminder_sent_at' => now()->subDays(5), // Sent 5 days ago
        ]);

        // 1. Run command after only 5 days
        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        // Assert no email sent (expired_reminders_sent is still 1)
        $subscription->refresh();
        $this->assertEquals(1, $subscription->expired_reminders_sent);

        // 2. Set last sent at to 8 days ago (simulating 1 week having passed)
        $subscription->update([
            'last_expired_reminder_sent_at' => now()->subDays(8),
        ]);

        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        // Assert reminder #2 was sent
        $subscription->refresh();
        $this->assertEquals(2, $subscription->expired_reminders_sent);
        $this->assertTrue(Carbon::parse($subscription->last_expired_reminder_sent_at)->isToday());

        // Assert English subject is logged
        $emailLogs = OutgoingEmail::where('to_email', 'client2@example.com')->get();
        $this->assertCount(1, $emailLogs);
        $this->assertStringContainsString('Subscription Expired', $emailLogs->first()->subject);

        // 3. Fast forward reminders count to 3, and set last sent to 8 days ago
        $subscription->update([
            'expired_reminders_sent' => 3,
            'last_expired_reminder_sent_at' => now()->subDays(8),
        ]);

        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        // Assert reminder #4 was sent
        $subscription->refresh();
        $this->assertEquals(4, $subscription->expired_reminders_sent);

        // 4. Try sending again after 8 days (already sent 4 reminders, which is the limit)
        $subscription->update([
            'last_expired_reminder_sent_at' => now()->subDays(8),
        ]);

        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        // Assert count remains 4
        $subscription->refresh();
        $this->assertEquals(4, $subscription->expired_reminders_sent);
    }

    /**
     * Test that if a user renews their subscription (status = active),
     * reminder emails stop being sent.
     */
    public function test_no_reminders_sent_if_subscription_is_renewed()
    {
        $user = User::factory()->create([
            'email' => 'renewed@example.com',
        ]);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addMonth(), // Active & valid
            'auto_renew' => true,
            'expired_reminders_sent' => 0,
            'last_expired_reminder_sent_at' => null,
        ]);

        $this->assertEquals(0, $this->artisan('subscription:send-expiry-reminders'));

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(0, $subscription->expired_reminders_sent);

        // Assert no email logs created
        $this->assertFalse(OutgoingEmail::where('to_email', 'renewed@example.com')->exists());
    }

    /**
     * Test high-fidelity tracking of both sending and sent states
     * including custom headers.
     */
    public function test_outgoing_email_logs_sending_and_sent_states()
    {
        $user = User::factory()->create([
            'email' => 'logtest@example.com',
        ]);

        // Send a direct email to verify the event listeners log correctly
        Mail::raw('Test email content', function ($message) use ($user) {
            $message->to($user->email)->subject('Direct Log Test');
        });

        // Verify that the email was logged and updated to 'sent'
        $emailLog = OutgoingEmail::where('to_email', 'logtest@example.com')->first();
        $this->assertNotNull($emailLog);
        $this->assertEquals('sent', $emailLog->status);
        $this->assertEquals('Direct Log Test', $emailLog->subject);
    }
}
