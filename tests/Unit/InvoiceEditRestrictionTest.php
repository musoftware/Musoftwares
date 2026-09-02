<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceEditRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_edited_returns_true_for_recent_unpaid_invoice()
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'unpaid',
            'currency_id' => 1,
            'created_at' => now('Africa/Cairo')->subDays(2),
        ]);

        $this->assertTrue($invoice->canBeEdited());
    }

    public function test_invoice_can_be_edited_returns_false_for_invoice_older_than_3_days()
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'unpaid',
            'currency_id' => 1,
            'created_at' => now()->subDays(4),
        ]);

        $this->assertFalse($invoice->canBeEdited());
    }

    public function test_invoice_can_be_edited_returns_false_for_paid_invoice()
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'paid',
            'currency_id' => 1,
            'created_at' => now('Africa/Cairo'),
        ]);

        $this->assertFalse($invoice->canBeEdited());
    }

    public function test_update_invoice_throws_exception_when_invoice_older_than_3_days()
    {
        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'unpaid',
            'currency_id' => 1,
            'created_at' => now('Africa/Cairo')->subDays(5),
        ]);

        $service = new InvoiceService();

        $this->expectException(\Exception::class);

        $service->updateInvoice($invoice, [
            'discount' => 10,
        ]);
    }
}
