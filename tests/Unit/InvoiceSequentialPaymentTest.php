<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSequentialPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_pay_newer_invoice_when_older_unpaid_invoice_exists()
    {
        $client = User::factory()->create();

        $olderInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $newerInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $this->assertTrue($newerInvoice->id > $olderInvoice->id);

        // Attempting to pay newer invoice should throw Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("older invoice #{$olderInvoice->id} is still unpaid");

        $newerInvoice->mark_as_paid();
    }

    public function test_can_pay_invoices_sequentially_in_order()
    {
        $client = User::factory()->create();

        $olderInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $newerInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        // 1. Pay older invoice first
        $olderInvoice->mark_as_paid();
        $this->assertEquals('paid', $olderInvoice->fresh()->status);

        // 2. Pay newer invoice second -> succeeds now that older is paid
        $newerInvoice->mark_as_paid();
        $this->assertEquals('paid', $newerInvoice->fresh()->status);
    }

    public function test_can_pay_newer_invoice_if_older_invoice_was_cancelled()
    {
        $client = User::factory()->create();

        $olderInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'cancelled',
            'currency_id' => 1,
        ]);

        $newerInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        // Paying newer invoice succeeds because older is cancelled
        $newerInvoice->mark_as_paid();
        $this->assertEquals('paid', $newerInvoice->fresh()->status);
    }

    public function test_invoice_service_mark_paid_enforces_sequential_rule()
    {
        $client = User::factory()->create();

        $olderInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $newerInvoice = Invoice::create([
            'user_id' => $client->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $service = new InvoiceService;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("older invoice #{$olderInvoice->id} is still unpaid");

        $service->markPaid($newerInvoice);
    }
}
