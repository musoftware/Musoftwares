<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\PaymentInvoices;
use Tests\TestCase;

/** Every payment in the AI builder is an invoice in the customer's account; a price paid in parts is one invoice. */
class PaymentInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private function project(?int $userId): BuilderProject
    {
        \App\Models\Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$']);

        return BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'title' => 'متجر', 'amount' => 100, 'quote_version' => 1, 'user_id' => $userId]);
    }

    public function test_a_deposit_is_a_partly_paid_invoice_and_the_rest_completes_the_same_one(): void
    {
        $customer = User::factory()->google()->create();
        $project = $this->project($customer->id);
        $balanceBefore = (float) $customer->balance();
        $invoices = app(PaymentInvoices::class);

        $invoices->quotePaid($project, 50);
        $invoice = Invoice::where('user_id', $customer->id)->sole();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertEqualsWithDelta(100, $invoice->final_total, 0.01);
        $this->assertEqualsWithDelta(50, $invoice->unpaid, 0.01);

        $invoices->quotePaid($project->refresh(), 50);
        $this->assertSame('paid', $invoice->refresh()->status);
        $this->assertSame(1, Invoice::where('user_id', $customer->id)->count());
        // the money came through the gateway: the invoice only records it, the wallet is not touched
        $this->assertEqualsWithDelta($balanceBefore, (float) $customer->refresh()->balance(), 0.01);
    }

    public function test_a_payment_paid_even_while_an_older_invoice_is_unpaid(): void
    {
        $customer = User::factory()->google()->create();
        Invoice::create(['user_id' => $customer->id, 'status' => 'unpaid']);

        app(PaymentInvoices::class)->feePaid($this->project($customer->id), 'فحص المشروع', 5);

        $this->assertSame('paid', Invoice::where('user_id', $customer->id)->latest('id')->first()->status);
    }

    public function test_a_guest_without_an_account_gets_no_invoice_and_the_payment_still_stands(): void
    {
        app(PaymentInvoices::class)->feePaid($this->project(null), 'فحص المشروع', 5);

        $this->assertSame(0, Invoice::count());
    }
}
