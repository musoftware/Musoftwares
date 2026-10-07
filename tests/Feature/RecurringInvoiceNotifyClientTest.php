<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\RecurringInvoiceInsufficientBalanceNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecurringInvoiceNotifyClientTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Feature\Concerns\SeedsUsdEgpRates;

    protected Currency $currency;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();

        $this->currency = Currency::where('currency', 'USD')->first() ?? Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f',
            'country' => 'US',
            'isocode' => 'USD',
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'currency_id' => $this->currency->id,
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_notify_client_sends_insufficient_balance_notification_when_latest_invoice_is_unpaid(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'user_balance' => 0,
        ]);

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Server Hosting Retainer',
            'amount' => 150,
            'currency_id' => $this->currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $recurring->apply();

        $latestInvoice = Invoice::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($latestInvoice);
        $this->assertEquals('unpaid', $latestInvoice->status);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.recurring_invoices.notify_client', $recurring->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            RecurringInvoiceInsufficientBalanceNotification::class,
            function ($notification) use ($latestInvoice) {
                return $notification->invoice->id === $latestInvoice->id;
            }
        );
    }

    public function test_notify_client_sends_paid_notification_when_latest_invoice_is_paid(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'user_balance' => 500,
        ]);

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Software Support License',
            'amount' => 100,
            'currency_id' => $this->currency->id,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $recurring->apply();

        $latestInvoice = Invoice::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($latestInvoice);
        $this->assertEquals('paid', $latestInvoice->status);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.recurring_invoices.notify_client', $recurring->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            InvoicePaidNotification::class,
            function ($notification) use ($latestInvoice) {
                return $notification->invoice->id === $latestInvoice->id;
            }
        );
    }

    public function test_notify_client_returns_error_when_no_generated_invoices_exist(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $recurring = RecurringInvoice::create([
            'user_id' => $user->id,
            'title' => 'Future Retainer',
            'amount' => 200,
            'currency_id' => $this->currency->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'current_date' => now()->addDays(10)->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.recurring_invoices.notify_client', $recurring->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        Notification::assertNothingSent();
    }
}
