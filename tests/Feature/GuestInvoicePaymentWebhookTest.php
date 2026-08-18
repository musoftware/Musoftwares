<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestInvoicePaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_invoice_payment_webhook_exempt_from_csrf()
    {
        config(['services.kashier.secret_key' => 'test_secret_key']);

        $currency = Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'EGP',
                'symbol' => 'EGP',
                'string_format' => '%01.2f EGP',
                'country' => 'EG',
                'isocode' => 'EGP',
                'rate' => 1,
            ]
        );

        $user = User::factory()->create(['currency_id' => $currency->id]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 1000,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Service payment',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 1000,
            'currency' => 'EGP',
        ]);

        $payload = [
            'platform' => 'kashier',
            'event' => 'pay',
            'data' => [
                'merchantOrderId' => "inv_{$invoice->id}_6a809d141e9c9-{$user->id}",
                'kashierOrderId' => 'test-kashier-order',
                'orderReference' => 'ORD-31307281',
                'transactionId' => 'TX-24073468594',
                'status' => 'SUCCESS',
                'method' => 'card',
                'amount' => 1000,
                'currency' => 'EGP',
                'signatureKeys' => [
                    'amount',
                    'currency',
                    'merchantOrderId',
                    'status',
                    'transactionId',
                ],
            ],
        ];

        // Compute valid signature for the test
        $secretKey = 'test_secret_key';
        $sigData = [
            'amount' => 1000,
            'currency' => 'EGP',
            'merchantOrderId' => "inv_{$invoice->id}_6a809d141e9c9-{$user->id}",
            'status' => 'SUCCESS',
            'transactionId' => 'TX-24073468594',
        ];
        $queryString = http_build_query($sigData, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey, false);

        $response = $this->postJson('/guest/invoices/payment/webhook', $payload, [
            'x-kashier-signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_billing_invoice_payment_webhook_exempt_from_csrf()
    {
        config(['services.kashier.secret_key' => 'test_secret_key']);

        $currency = Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'EGP',
                'symbol' => 'EGP',
                'string_format' => '%01.2f EGP',
                'country' => 'EG',
                'isocode' => 'EGP',
                'rate' => 1,
            ]
        );

        $user = User::factory()->create(['currency_id' => $currency->id]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 500,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Billing invoice',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 500,
            'currency' => 'EGP',
        ]);

        $payload = [
            'platform' => 'kashier',
            'event' => 'pay',
            'data' => [
                'merchantOrderId' => "u_inv_{$invoice->id}_uniq456-{$user->id}",
                'kashierOrderId' => 'test-kashier-order-2',
                'orderReference' => 'ORD-98765',
                'transactionId' => 'TX-123456',
                'status' => 'SUCCESS',
                'method' => 'card',
                'amount' => 500,
                'currency' => 'EGP',
                'signatureKeys' => [
                    'amount',
                    'currency',
                    'merchantOrderId',
                    'status',
                    'transactionId',
                ],
            ],
        ];

        $secretKey = 'test_secret_key';
        $sigData = [
            'amount' => 500,
            'currency' => 'EGP',
            'merchantOrderId' => "u_inv_{$invoice->id}_uniq456-{$user->id}",
            'status' => 'SUCCESS',
            'transactionId' => 'TX-123456',
        ];
        $queryString = http_build_query($sigData, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $secretKey, false);

        $response = $this->postJson('/billing/invoices/payment/webhook', $payload, [
            'x-kashier-signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }
}
