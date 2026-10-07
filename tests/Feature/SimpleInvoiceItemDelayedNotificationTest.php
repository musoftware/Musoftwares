<?php

namespace Tests\Feature;

use App\Events\InvoiceCreated;
use App\Events\InvoiceItemAdded;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\InvoiceItemAddedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SimpleInvoiceItemDelayedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::where('currency', 'USD')->first() ?? Currency::create([
            'currency' => 'USD',
            'symbol' => '$',
            'string_format' => '$%01.2f',
            'country' => 'US',
            'isocode' => 'USD',
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_invoice_created_with_simple_item_delays_notification_by_one_hour(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'email' => 'client_simple@example.com',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Web Design Service',
            'qty' => 1,
            'amount' => 300,
            'item_type' => 'simple',
        ]);

        event(new InvoiceCreated($invoice->fresh()));

        Notification::assertSentTo(
            $user,
            InvoiceCreatedNotification::class,
            function ($notification) {
                return $notification->delay !== null;
            }
        );
    }

    public function test_invoice_created_without_simple_item_sends_notification_immediately(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'email' => 'client_nonsimple@example.com',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Development Hours',
            'qty' => 5,
            'amount' => 50,
            'item_type' => 'timer',
        ]);

        event(new InvoiceCreated($invoice->fresh()));

        Notification::assertSentTo(
            $user,
            InvoiceCreatedNotification::class,
            function ($notification) {
                return $notification->delay === null;
            }
        );
    }

    public function test_invoice_item_added_with_simple_type_delays_notification_by_one_hour(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'email' => 'client_item@example.com',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Extra Domain Name',
            'qty' => 1,
            'amount' => 20,
            'item_type' => 'simple',
        ]);

        event(new InvoiceItemAdded($invoice->fresh(), $item));

        Notification::assertSentTo(
            $user,
            InvoiceItemAddedNotification::class,
            function ($notification) {
                return $notification->delay !== null;
            }
        );
    }
}
