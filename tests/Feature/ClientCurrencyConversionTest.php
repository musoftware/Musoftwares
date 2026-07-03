<?php

namespace Tests\Feature;

use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\ClientCurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientCurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    private const USD = 1;

    private const EGP = 2;

    private const EUR = 3;

    /** USD -> EGP rate seeded for "today". */
    private const USD_TO_EGP = 50.0;

    protected function setUp(): void
    {
        parent::setUp();

        CurrenciesExchange::flushCache();

        AdminSettings::updateOrCreate(['setting_key' => 'business_currency'], ['setting_value' => (string) self::USD]);
        AdminSettings::updateOrCreate(['setting_key' => 'exchange_update_date'], ['setting_value' => now()->toDateString()]);

        Currency::firstOrCreate(['id' => self::USD], ['currency' => 'USD', 'symbol' => '$']);
        Currency::firstOrCreate(['id' => self::EGP], ['currency' => 'EGP', 'symbol' => 'EGP']);
        Currency::firstOrCreate(['id' => self::EUR], ['currency' => 'EUR', 'symbol' => 'EUR']);

        $today = now()->toDateString();
        $this->seedRate(self::USD, self::EGP, self::USD_TO_EGP, $today);
        $this->seedRate(self::EGP, self::USD, 1 / self::USD_TO_EGP, $today);
        $this->seedRate(self::USD, self::EUR, 0.9, $today);
        $this->seedRate(self::EUR, self::USD, 1.1111, $today);
    }

    public function test_convert_rewrites_all_monetary_rows_to_new_currency(): void
    {
        [$user, $data, $project] = $this->seedUsdClient();

        $this->convertUsdToEgp($user);

        $exp = fn ($amount) => round(self::USD_TO_EGP * $amount, 2);

        // transactions
        $t = DB::table('transactions')->where('user_id', $user->id)->orderBy('id')->get();
        $this->assertCount(2, $t);
        $this->assertAmount($exp(100), $t[0]->amount);
        $this->assertEquals(self::EGP, (int) $t[0]->currency_id);
        $this->assertAmount($exp(200), $t[1]->amount);
        $this->assertEquals(self::EGP, (int) $t[1]->currency_id);

        // cost_transactions
        $ct = DB::table('cost_transactions')->where('user_id', $user->id)->first();
        $this->assertAmount($exp(30), $ct->amount);
        $this->assertEquals(self::EGP, (int) $ct->currency_id);

        // recurring_invoices
        $ri = DB::table('recurring_invoices')->where('user_id', $user->id)->first();
        $this->assertAmount($exp(120), $ri->amount);
        $this->assertEquals(self::EGP, (int) $ri->currency_id);

        // withdrawal
        $w = DB::table('user_referral_request_withdraws')->where('user_id', $user->id)->first();
        $this->assertAmount($exp(50), $w->amount);
        $this->assertEquals(self::EGP, (int) $w->currency_id);

        // invoices + items + timers
        $inv1 = DB::table('invoices')->where('id', $data['inv1'])->first();
        $this->assertEquals(self::EGP, (int) $inv1->currency_id);
        $this->assertAmount($exp(100), $inv1->paid);

        $inv2 = DB::table('invoices')->where('id', $data['inv2'])->first();
        $this->assertEquals(self::EGP, (int) $inv2->currency_id);
        $this->assertAmount($exp(80), $inv2->unpaid);
        $this->assertAmount($exp(5), $inv2->tax_value);
        $this->assertAmount($exp(2), $inv2->cost);

        $item1 = DB::table('invoice_items')->where('id', $data['item1'])->first();
        $this->assertAmount($exp(100), $item1->amount);

        $item2 = DB::table('invoice_items')->where('id', $data['item2'])->first();
        $this->assertAmount($exp(80), $item2->amount);

        $timer2 = DB::table('invoice_item_timers')->where('id', $data['timer2'])->first();
        $this->assertAmount($exp(20), $timer2->amount);
        $this->assertEquals(self::EGP, (int) $timer2->currency_id);

        // Denormalized balances recomputed from converted source (1:1 in user currency).
        $user = $user->fresh();
        $this->assertAmount($exp(300) - $exp(50), (float) $user->user_balance); // 15000 - 2500
        $this->assertAmount($exp(300), (float) $user->total_paid);              // 15000
        $this->assertAmount($exp(30), (float) $user->total_cost);               // 1500
        $this->assertAmount($exp(50), (float) $user->withdrawing_commission);   // 2500

        $project = $project->fresh();
        $this->assertAmount($exp(200), (float) $project->project_balance);      // 10000
        $this->assertAmount($exp(200), (float) $project->total_paid);           // 10000
    }

    public function test_paid_invoice_total_remains_consistent_after_conversion(): void
    {
        [$user, $data] = $this->seedUsdClient();

        $this->convertUsdToEgp($user);

        $invoice = Invoice::with('items.timers')->find($data['inv1']);

        // total() = sub_total + tax - discounts; for the seeded paid invoice
        // (1 item @100, qty 1, no tax/discount) total must equal paid + unpaid.
        $this->assertAmount(round(self::USD_TO_EGP * 100, 2), $invoice->total());
        $this->assertAmount(
            round($invoice->paid + $invoice->unpaid, 2),
            round($invoice->total(), 2)
        );
    }

    public function test_double_conversion_guard_is_idempotent(): void
    {
        [$user] = $this->seedUsdClient();

        $this->convertUsdToEgp($user);

        $amountAfterFirst = (float) DB::table('transactions')->where('user_id', $user->id)->value('amount');

        // Second identical pass: no row still carries USD, so nothing is touched.
        app(ClientCurrencyConverterService::class)->convert($user, self::USD, self::EGP);

        $amountAfterSecond = (float) DB::table('transactions')->where('user_id', $user->id)->value('amount');

        $this->assertSame($amountAfterFirst, $amountAfterSecond);
    }

    public function test_admin_update_triggers_conversion_when_currency_changes(): void
    {
        [$user] = $this->seedUsdClient();

        $request = Request::create('/', 'POST', [
            'name' => $user->name,
            'email' => $user->email,
            'currency' => self::EGP,
        ]);

        app(AdminUserService::class)->updateFromRequest($user, $request);

        $this->assertEquals(self::EGP, (int) $user->fresh()->currency_id);
        $this->assertEquals(self::EGP, (int) DB::table('transactions')->where('user_id', $user->id)->value('currency_id'));
        $this->assertAmount(round(self::USD_TO_EGP * 100, 2), (float) DB::table('transactions')->where('user_id', $user->id)->value('amount'));
    }

    public function test_admin_update_without_currency_change_is_a_noop(): void
    {
        [$user] = $this->seedUsdClient();

        $originalAmount = (float) DB::table('transactions')->where('user_id', $user->id)->value('amount');
        $originalBalance = (float) $user->user_balance;

        $request = Request::create('/', 'POST', [
            'name' => 'Renamed Client',
            'email' => $user->email,
            'currency' => self::USD, // unchanged
        ]);

        app(AdminUserService::class)->updateFromRequest($user, $request);

        $this->assertSame($originalAmount, (float) DB::table('transactions')->where('user_id', $user->id)->value('amount'));
        $this->assertEquals(self::USD, (int) DB::table('transactions')->where('user_id', $user->id)->value('currency_id'));
        $this->assertSame($originalBalance, (float) $user->fresh()->user_balance);
    }

    /**
     * Mirror the production contract: persist the new currency on the user, then
     * run the converter (the AdminUserService trigger saves the currency first).
     */
    private function convertUsdToEgp(User $user): void
    {
        $user->currency_id = self::EGP;
        $user->save();

        app(ClientCurrencyConverterService::class)->convert($user, self::USD, self::EGP);
    }

    /**
     * Seed a USD client with a representative data set. Returns [$user, $ids, $project].
     */
    private function seedUsdClient(): array
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        $now = now();

        $project = Project::create([
            'user_id' => $user->id,
            'project_name' => 'Test Project',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // transactions (currency matches user -> observer leaves amount intact)
        DB::table('transactions')->insert([
            ['user_id' => $user->id, 'amount' => 100, 'reason' => 't1', 'type' => 'received', 'currency_id' => self::USD, 'project_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => $user->id, 'amount' => 200, 'reason' => 't2', 'type' => 'sent', 'currency_id' => self::USD, 'project_id' => $project->id, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('cost_transactions')->insert([
            'user_id' => $user->id, 'amount' => 30, 'reason' => 'ct1', 'currency_id' => self::USD, 'project_id' => null, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('recurring_invoices')->insert([
            'user_id' => $user->id, 'currency_id' => self::USD, 'title' => 'ri1', 'amount' => 120, 'start_date' => $now->toDateString(), 'recurring' => 'month', 'recurring_times' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $paymentMethodId = DB::table('user_payment_methods')->insertGetId([
            'user_id' => $user->id, 'type' => 'wallet', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('user_referral_request_withdraws')->insert([
            'user_id' => $user->id, 'currency_id' => self::USD, 'amount' => 50, 'user_payment_method_id' => $paymentMethodId, 'payment_method' => 'wallet', 'payment_info' => 'n/a', 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $inv1 = DB::table('invoices')->insertGetId([
            'user_id' => $user->id, 'currency_id' => self::USD, 'uuid' => Str::uuid(),
            'paid' => 100, 'unpaid' => 0, 'discount' => 0, 'second_discount' => 0, 'tax_value' => 0, 'cost' => 0,
            'status' => 'paid', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $item1 = DB::table('invoice_items')->insertGetId([
            'invoice_id' => $inv1, 'amount' => 100, 'item_title' => 'item1', 'item_type' => 'simple', 'qty' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $inv2 = DB::table('invoices')->insertGetId([
            'user_id' => $user->id, 'currency_id' => self::USD, 'uuid' => Str::uuid(),
            'paid' => 0, 'unpaid' => 80, 'discount' => 0, 'second_discount' => 0, 'tax_value' => 5, 'cost' => 2,
            'status' => 'unpaid', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $item2 = DB::table('invoice_items')->insertGetId([
            'invoice_id' => $inv2, 'amount' => 80, 'item_title' => 'item2', 'item_type' => 'simple', 'qty' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $timer2 = DB::table('invoice_item_timers')->insertGetId([
            'user_id' => $user->id, 'invoice_item_id' => $item2, 'project_id' => null, 'amount' => 20, 'currency_id' => self::USD, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return [$user, [
            'inv1' => $inv1, 'inv2' => $inv2, 'item1' => $item1, 'item2' => $item2, 'timer2' => $timer2,
        ], $project];
    }

    private function seedRate(int $cur1, int $cur2, float $rate, string $date): void
    {
        CurrenciesExchange::create([
            'currency1' => $cur1,
            'currency2' => $cur2,
            'rate' => $rate,
            'date_string' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertAmount(float $expected, $actual, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, (float) $actual, 0.01, $message);
    }
}
