<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class RecurringAndAutoPaySafetyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seedUsdEgpRates();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usdClient(float $balance): User
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        $user->forceFill(['user_balance' => $balance])->save();

        return $user;
    }

    private function recurringInvoice(User $user, array $overrides = []): RecurringInvoice
    {
        return RecurringInvoice::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Retainer',
            'amount' => 100,
            'currency_id' => self::USD,
            'start_date' => now()->toDateString(),
            'current_date' => now()->toDateString(),
            'recurring' => 'day',
            'recurring_times' => 1,
            'is_active' => true,
        ], $overrides));
    }

    private function unpaidInvoice(User $user, float $amount, int $ageDays): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => self::USD,
            'status' => 'unpaid',
            'paid' => 0,
            'unpaid' => $amount,
            'cost' => 0,
            'cost_calculated' => '0',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Service',
            'item_type' => 'simple',
            'qty' => 1,
            'amount' => $amount,
            'currency' => 'USD',
        ]);
        $invoice->forceFill(['created_at' => now()->subDays($ageDays)])->save();

        return $invoice->fresh();
    }

    public function test_one_broken_recurring_invoice_does_not_stop_the_others(): void
    {
        $brokenOwner = $this->usdClient(0);
        $healthyOwner = $this->usdClient(0);
        $this->recurringInvoice($brokenOwner, ['current_date' => 'not-a-date']);
        $this->recurringInvoice($healthyOwner);

        $exitCode = Artisan::call('add:recurring_invoices');

        $this->assertEquals(1, $exitCode, 'The command should report the failed item.');
        $this->assertEquals(1, Invoice::where('user_id', $healthyOwner->id)->count());
        $this->assertEquals(0, Invoice::where('user_id', $brokenOwner->id)->count());
    }

    public function test_recurring_invoice_uses_the_business_calendar_date(): void
    {
        // 12:00 UTC on Oct 15 is already Oct 16 in a UTC+14 business timezone.
        config(['app.business_timezone' => 'Pacific/Kiritimati']);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'UTC'));

        $recurring = $this->recurringInvoice($this->usdClient(0), [
            'start_date' => '2026-10-01',
            'current_date' => '2026-10-01',
            'recurring' => 'month',
            'recurring_times_month' => '16',
            'days_before' => 0,
        ]);

        $recurring->apply();

        $this->assertDatabaseHas('recurring_invoice_records', [
            'recurring_invoice_id' => $recurring->id,
            'unique_id' => $recurring->id.'-2026-10-16',
        ]);
    }

    public function test_auto_bill_rechecks_wallet_and_refuses_when_it_cannot_pay(): void
    {
        $client = $this->usdClient(50);
        $invoice = $this->unpaidInvoice($client, 200, 5);

        try {
            $invoice->bill_invoice(true);
            $this->fail('Auto billing should refuse an invoice the wallet cannot cover.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Insufficient wallet balance', $e->getMessage());
        }

        $this->assertEquals(50.0, round((float) $client->fresh()->user_balance, 2));
        $this->assertEquals('unpaid', $invoice->fresh()->status);
    }

    public function test_auto_pay_command_pays_covered_invoices_and_skips_the_rest(): void
    {
        $richClient = $this->usdClient(500);
        $poorClient = $this->usdClient(10);
        $covered = $this->unpaidInvoice($richClient, 200, 5);
        $uncovered = $this->unpaidInvoice($poorClient, 200, 5);

        Artisan::call('invoices:auto-pay');

        $this->assertEquals('paid', $covered->fresh()->status);
        $this->assertEquals(300.0, round((float) $richClient->fresh()->user_balance, 2));
        $this->assertEquals('unpaid', $uncovered->fresh()->status);
        $this->assertEquals(10.0, round((float) $poorClient->fresh()->user_balance, 2));
    }
}
