<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Notifications\InvoicePaidNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoicePaidNotificationAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Currency::where('currency', 'EGP')->exists()) {
            Currency::create([
                'id' => 2,
                'currency' => 'EGP',
                'symbol' => 'e£',
                'string_format' => 'e£%01.2f',
                'country' => 'EG',
                'isocode' => 'EGP',
            ]);
        }
    }

    public function test_invoice_paid_notification_shows_correct_amount_in_email_and_fcm()
    {
        $user = User::factory()->create([
            'email' => 'client@example.com',
            'currency_id' => 2, // EGP
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => 2,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 2000,
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Services',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 2000,
        ]);

        // Refresh model to load relations or computed values
        $invoice = $invoice->fresh();

        $this->assertEquals(2000, $invoice->total());

        $notification = new InvoicePaidNotification($invoice);
        $mailMessage = $notification->toMail($user);

        // Verify the email content contains the formatted amount (e£2,000.00 or e£2000.00)
        $introLines = $mailMessage->introLines;
        $amountLineFound = false;

        foreach ($introLines as $line) {
            if (str_contains($line, 'We have successfully received your payment of')) {
                $amountLineFound = true;
                $this->assertStringContainsString('2,000.00', $line);
            }
        }

        $this->assertTrue($amountLineFound, 'Did not find payment confirmation line in email.');

        // Verify the FCM message contains the formatted amount
        $fcmMessage = $notification->toFcm($user);
        $this->assertStringContainsString('2,000.00', json_encode($fcmMessage->jsonSerialize()));
    }
}
