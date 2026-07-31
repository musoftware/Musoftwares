<?php

namespace Tests\Feature;

use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceCostLine;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default currency exists for testing
        $this->currency = Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'USD',
                'symbol' => '$',
                'string_format' => '$%01.2f',
                'country' => 'US',
                'isocode' => 'USD',
            ]
        );
    }

    /**
     * Test that repeated/concurrent calls to mark_as_paid() do not create duplicate transactions.
     */
    public function test_mark_as_paid_is_idempotent_and_prevents_duplicate_transactions(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 100,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Test Item',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 100,
            'currency' => 'USD',
        ]);

        // Call mark_as_paid twice sequentially to simulate race condition / retries
        $invoice->refresh();
        $invoice->mark_as_paid();
        $invoice->mark_as_paid();

        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(100, $invoice->paid);

        // Should attach exactly 2 transactions (received + used), NOT 4
        $attachedTransactionsCount = $invoice->transactions()->count();
        $this->assertEquals(2, $attachedTransactionsCount, 'Duplicate transactions attached to invoice!');

        // Total user transactions created for this invoice must be 2
        $userTransactionsCount = Transaction::where('user_id', $client->id)->count();
        $this->assertEquals(2, $userTransactionsCount, 'Duplicate transactions created on user balance!');
    }

    /**
     * Test that bill_invoice() does not double-charge balance or create duplicate transactions.
     */
    public function test_bill_invoice_is_idempotent_and_prevents_duplicate_deductions(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);
        $client->add_balance(500, 'Initial Deposit', 'received', $this->currency->id);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 200,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Service Fee',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 200,
            'currency' => 'USD',
        ]);

        // Bill invoice twice
        $invoice->refresh();
        $invoice->bill_invoice();
        $invoice->bill_invoice();

        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(200, $invoice->paid);
        $this->assertEquals(0, $invoice->unpaid);

        // Exactly 1 payment transaction attached (used), not 2
        $this->assertEquals(1, $invoice->transactions()->count(), 'Duplicate transactions attached during bill_invoice!');
    }

    /**
     * Test that calculate_cost() does not duplicate cost transactions when called multiple times.
     */
    public function test_calculate_cost_prevents_duplicate_cost_transactions(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 300,
            'cost' => 50,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Project Milestone',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 300,
            'currency' => 'USD',
        ]);

        // Mark paid (which triggers calculate_cost)
        $invoice->mark_as_paid();

        // Call calculate_cost explicitly again
        $invoice->refresh();
        $invoice->calculate_cost();

        $this->assertEquals('1', $invoice->cost_calculated);

        // CostTransaction should be created exactly once
        $costTxCount = CostTransaction::count();
        $this->assertEquals(1, $costTxCount, 'Cost transaction was created multiple times!');

        $attachedCostTxCount = $invoice->cost_transactions()->count();
        $this->assertEquals(1, $attachedCostTxCount, 'Duplicate cost transactions attached to invoice!');
    }

    /**
     * Test that cost lines (direct and user credit) are processed exactly once and not doubled.
     */
    public function test_cost_lines_direct_and_user_credit_are_not_doubled(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);
        $subcontractor = User::factory()->create(['currency_id' => $this->currency->id]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 1000,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Development',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 1000,
            'currency' => 'USD',
        ]);

        $directLine = InvoiceCostLine::create([
            'invoice_id' => $invoice->id,
            'line_type' => 'direct',
            'amount' => 150,
            'description' => 'Server Hosting',
        ]);

        $creditLine = InvoiceCostLine::create([
            'invoice_id' => $invoice->id,
            'line_type' => 'user_credit',
            'amount' => 300,
            'description' => 'Subcontractor Payout',
            'credit_user_id' => $subcontractor->id,
        ]);

        // Call mark_as_paid twice
        $invoice->mark_as_paid();
        $invoice->mark_as_paid();

        $invoice->refresh();
        $directLine->refresh();
        $creditLine->refresh();

        // Direct line cost transaction assigned once
        $this->assertNotNull($directLine->cost_transaction_id);
        $this->assertNotNull($creditLine->earned_transaction_id);

        // Only 1 cost transaction created for the direct line
        $this->assertEquals(1, CostTransaction::count(), 'Direct cost line transaction duplicated!');

        // Subcontractor earned balance should equal 300, not 600
        $this->assertEquals(300, $subcontractor->refresh()->balance($this->currency->id), 'Subcontractor balance was credited multiple times!');
    }

    /**
     * Test that an unpaid invoice never executes cost calculation.
     */
    public function test_unpaid_invoice_does_not_calculate_cost(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 500,
            'cost' => 100,
            'cost_calculated' => '0',
        ]);

        $invoice->calculate_cost();

        $this->assertEquals('0', $invoice->cost_calculated);
        $this->assertEquals(0, CostTransaction::count());
    }

    /**
     * Test simulated race condition where two separate instances of the same invoice call mark_as_paid simultaneously.
     */
    public function test_simulated_race_condition_concurrent_mark_as_paid(): void
    {
        $client = User::factory()->create(['currency_id' => $this->currency->id]);

        $invoice = Invoice::create([
            'user_id' => $client->id,
            'currency_id' => $this->currency->id,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => 400,
            'cost' => 80,
            'cost_calculated' => '0',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Design Work',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => 400,
            'currency' => 'USD',
        ]);

        // Load two separate instance handles for the same invoice ID
        $instanceA = Invoice::find($invoice->id);
        $instanceB = Invoice::find($invoice->id);

        // Execute payment on instance A, then on instance B
        $instanceA->mark_as_paid();
        $instanceB->mark_as_paid();

        $freshInvoice = $invoice->fresh();

        $this->assertEquals('paid', $freshInvoice->status);
        $this->assertEquals(2, $freshInvoice->transactions()->count(), 'Race condition caused duplicate transactions!');
        $this->assertEquals(1, CostTransaction::count(), 'Race condition caused duplicated cost transactions!');
    }
}
