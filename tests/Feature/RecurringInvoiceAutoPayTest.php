<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Notifications\RecurringInvoiceInsufficientBalanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecurringInvoiceAutoPayTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            [
                'symbol' => '$',
                'string_format' => '$%01.2f',
                'country' => 'US',
                'isocode' => 'USD',
            ]
        );
    }

    public function test_recurring_invoice_auto_pays_when_client_has_sufficient_balance(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'user_balance' => 500,
        ]);

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Monthly Retainer',
            'amount' => 100,
            'currency_id' => $this->currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $recurring->apply();

        $invoice = Invoice::where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, (float) $invoice->unpaid);
        $this->assertEquals(100, (float) $invoice->paid);

        $user->refresh();
        $this->assertEquals(400, (float) $user->user_balance);

        Notification::assertNotSentTo($user, RecurringInvoiceInsufficientBalanceNotification::class);
    }

    public function test_recurring_invoice_remains_unpaid_and_sends_notification_when_balance_insufficient(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'user_balance' => 50,
        ]);

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Monthly Retainer',
            'amount' => 100,
            'currency_id' => $this->currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $recurring->apply();

        $invoice = Invoice::where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(100, (float) $invoice->unpaid);
        $this->assertEquals(0, (float) $invoice->paid);

        $user->refresh();
        $this->assertEquals(50, (float) $user->user_balance);

        Notification::assertSentTo(
            $user,
            RecurringInvoiceInsufficientBalanceNotification::class,
            function ($notification, $channels) use ($invoice) {
                return $notification->invoice->id === $invoice->id
                    && in_array('mail', $channels, true)
                    && in_array('fcm', $channels, true);
            }
        );
    }
}
