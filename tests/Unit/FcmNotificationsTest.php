<?php

namespace Tests\Unit;

use App\Events\AmountReceived;
use App\Events\ContractSigned;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceCreated;
use App\Events\InvoiceItemAdded;
use App\Events\InvoicePaid;
use App\Events\SerialUserDeviceStatusChanged;
use App\Helpers\MuFcmChannel;
use App\Listeners\NotificationEventListener;
use App\Models\User;
use App\Notifications\AmountReceivedNotification;
use App\Notifications\ContractSignedNotification;
use App\Notifications\InvoiceCancelledNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\InvoiceItemAddedNotification;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\SerialUserDeviceStatusChangedNotification;
use App\Notifications\SubscriptionPaymentFailedNotification;
use App\Notifications\WithdrawalApprovedNotification;
use App\Notifications\WithdrawalRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Messaging\CloudMessage;
use Tests\TestCase;

class FcmNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AmountReceivedNotification resolves the currency name via Currency::find().
        // The suite's app-level migrations don't run without RefreshDatabase, so
        // ensure a minimal currencies table exists for this DB-free unit test.
        try {
            Schema::create('currencies', function ($table) {
                $table->id();
                $table->string('currency')->nullable();
                $table->softDeletes();
            });
            DB::table('currencies')->insert([
                'id' => 1,
                'currency' => 'USD',
            ]);
        } catch (\Throwable $e) {
            // Table may already exist; ignore.
        }
    }

    /**
     * All notifications that must expose an FCM channel.
     */
    public static function fcmNotificationsProvider(): array
    {
        $invoice = (object) ['id' => 1, 'invoice_number' => 'INV-1', 'amount' => 100, 'currency_id' => 1];
        $item = (object) ['item_title' => 'Design'];

        return [
            'invoice_paid' => [new InvoicePaidNotification($invoice)],
            'invoice_created' => [new InvoiceCreatedNotification($invoice)],
            'invoice_item_added' => [new InvoiceItemAddedNotification($invoice, $item)],
            'invoice_cancelled' => [new InvoiceCancelledNotification($invoice)],
            'amount_received' => [new AmountReceivedNotification(50, 1)],
            'withdrawal_requested' => [new WithdrawalRequestedNotification((object) ['id' => 1, 'amount' => 50, 'currency' => 'USD'])],
            'withdrawal_approved' => [new WithdrawalApprovedNotification((object) ['id' => 1, 'amount' => 50, 'currency' => 'USD'])],
            'subscription_failed' => [new SubscriptionPaymentFailedNotification('ERP')],
            'contract_signed' => [new ContractSignedNotification((object) ['id' => 1])],
            'device_status_changed' => [new SerialUserDeviceStatusChangedNotification((object) ['id' => 1, 'device_id' => 'DEV1'], 'active', 'inactive')],
        ];
    }

    /**
     * @dataProvider fcmNotificationsProvider
     */
    public function test_notification_exposes_fcm_channel_and_message($notification): void
    {
        $this->assertContains('fcm', $notification->via(new class {}));

        $message = $notification->toFcm(new class {});

        $this->assertInstanceOf(CloudMessage::class, $message);
    }

    public function test_fcm_channel_is_registered(): void
    {
        $channel = app(ChannelManager::class)->channel('fcm');

        $this->assertInstanceOf(MuFcmChannel::class, $channel);
    }

    public function test_event_listener_routes_each_event_to_correct_notification(): void
    {
        Notification::fake();

        $user = new User;
        $user->id = 99;
        $user->email = 'client@example.com';
        $user->name = 'Client';

        // Build stub domain objects whose client/user relations resolve to our user.
        $invoice = $this->makeInvoice($user);
        $contract = $this->makeModelWithUser($user);
        $device = $this->makeModelWithUser($user);

        $listener = new NotificationEventListener;

        $listener->handle(new InvoicePaid($invoice));
        $listener->handle(new InvoiceCreated($invoice));
        $listener->handle(new InvoiceItemAdded($invoice, (object) ['item_title' => 'X']));
        $listener->handle(new InvoiceCancelled($invoice));
        $listener->handle(new AmountReceived($user, 10, 'reason', 1));
        $listener->handle(new ContractSigned($contract));
        $listener->handle(new SerialUserDeviceStatusChanged($device, 'active', 'inactive'));

        Notification::assertSentTo($user, InvoicePaidNotification::class);
        Notification::assertSentTo($user, InvoiceCreatedNotification::class);
        Notification::assertSentTo($user, InvoiceItemAddedNotification::class);
        Notification::assertSentTo($user, InvoiceCancelledNotification::class);
        Notification::assertSentTo($user, AmountReceivedNotification::class);
        Notification::assertSentTo($user, ContractSignedNotification::class);
        Notification::assertSentTo($user, SerialUserDeviceStatusChangedNotification::class);
    }

    public function test_invoice_paid_falls_back_to_mail_route_without_user(): void
    {
        Notification::fake();

        // Invoice whose client resolves to null but has an email attribute.
        $client = new \stdClass;
        $client->email = 'legacy@example.com';

        $invoice = $this->makeInvoice($client, false);

        $listener = new NotificationEventListener;
        $listener->handle(new InvoicePaid($invoice));

        Notification::assertSentTo(
            Notification::route('mail', 'legacy@example.com'),
            InvoicePaidNotification::class
        );
    }

    /**
     * Build a minimal stub that mimics an Invoice with a client relation.
     */
    private function makeInvoice($client, bool $clientIsUser = true)
    {
        $invoice = new \stdClass;
        $invoice->id = 1;
        $invoice->invoice_number = 'INV-1';
        $invoice->amount = 100;
        $invoice->currency_id = 1;

        // NotificationEventListener::resolveInvoiceRecipient inspects ->client
        // and checks instanceof User. We expose client via a closure property
        // isn't supported on stdClass, so emulate using a small anonymous class.
        return new class($client)
        {
            public $client;

            public function __construct($client)
            {
                $this->client = $client;
            }
        };
    }

    private function makeModelWithUser($user)
    {
        return new class($user)
        {
            public $user;

            public function __construct($user)
            {
                $this->user = $user;
            }

            public function user()
            {
                return $this->user;
            }
        };
    }
}
