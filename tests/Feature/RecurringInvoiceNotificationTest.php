<?php

namespace Tests\Feature;

use App\Events\InvoiceCreated;
use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Verifies that when a RecurringInvoice materializes an Invoice, the
 * InvoiceCreated event fires and the InvoiceCreatedNotification is dispatched
 * via the channels configured globally in AdminSettings.
 *
 * The setting is tenant-wide (set once in Admin -> Settings) — there is no
 * per-invoice channel toggle.
 */
class RecurringInvoiceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Currency::where('currency', 'USD')->exists()) {
            Currency::create([
                'currency' => 'USD',
                'symbol' => '$',
                'string_format' => '$%01.2f',
                'country' => 'US',
                'isocode' => 'USD',
            ]);
        }
    }

    public function test_default_channels_are_mail_and_fcm_when_no_setting(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'client@example.com']);
        $currency = Currency::where('currency', 'USD')->first();

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Weekly Maintenance',
            'amount' => 100,
            'currency_id' => $currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'week',
            'recurring_times' => 1,
            // Schedule for whatever today actually is so apply() actually fires.
            'recurring_times_week' => date('l'),
            'is_active' => true,
        ]);

        $recurring->apply();

        Notification::assertSentTo(
            $user,
            InvoiceCreatedNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels, true)
                    && in_array('fcm', $channels, true)
                    && ! in_array('sms', $channels, true)
                    && ! in_array('whatsapp', $channels, true);
            }
        );
    }

    public function test_admin_setting_overrides_default_channels(): void
    {
        Notification::fake();

        AdminSettings::SetValue('notif_channels_invoice_created', 'mail,sms,whatsapp');

        $user = User::factory()->create();
        $currency = Currency::where('currency', 'USD')->first();

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Daily Retainer',
            'amount' => 50,
            'currency_id' => $currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $recurring->apply();

        Notification::assertSentTo(
            $user,
            InvoiceCreatedNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels, true)
                    && in_array('sms', $channels, true)
                    && in_array('whatsapp', $channels, true)
                    && ! in_array('fcm', $channels, true);
            }
        );
    }

    public function test_invoice_created_event_is_dispatched_when_apply_creates_invoice(): void
    {
        Event::fake([InvoiceCreated::class]);
        Notification::fake();

        $user = User::factory()->create();
        $currency = Currency::where('currency', 'USD')->first();

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Monthly Subscription',
            'amount' => 75,
            'currency_id' => $currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => now()->format('j'),
            'is_active' => true,
        ]);

        $recurring->apply();

        Event::assertDispatched(InvoiceCreated::class);
    }
}
