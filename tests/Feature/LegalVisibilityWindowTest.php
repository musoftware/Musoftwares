<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConsentAction;
use App\Enums\ConsentType;
use App\Helpers\BalancesHelper;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserConsentLog;
use App\Services\BalanceService;
use App\Support\LegalWindow;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class LegalVisibilityWindowTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['id' => 1],
            [
                'currency' => 'USD',
                'symbol' => '$',
                'string_format' => '$%01.2f',
                'is_default' => true,
            ]
        );
        Currency::firstOrCreate(
            ['id' => 2],
            [
                'currency' => 'EGP',
                'symbol' => 'e£',
                'string_format' => 'e£%01.2f',
                'is_default' => false,
            ]
        );

        AdminSettings::SetValue('business_currency', '1');
        $this->seedUsdEgpRates();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'currency_id' => 1,
            'onboarding_completed' => true,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function createClient(): User
    {
        $client = User::factory()->create([
            'currency_id' => 1,
            'onboarding_completed' => true,
        ]);
        $client->assignRole('client');

        return $client;
    }

    private function insertTransactionAt(User $client, float $amount, string $type, string $cairoDateTime, ?int $projectId = null): Transaction
    {
        $utcTimestamp = Carbon::parse($cairoDateTime, 'Africa/Cairo')->setTimezone('UTC');

        $tx = new Transaction;
        $tx->user_id = $client->id;
        $tx->project_id = $projectId;
        $tx->currency_id = 1;
        $tx->amount = $amount;
        $tx->business_amount = $amount;
        $tx->type = $type;
        $tx->reason = "Test {$type} at {$cairoDateTime}";
        $tx->saveQuietly();

        DB::table('transactions')->where('id', $tx->id)->update([
            'created_at' => $utcTimestamp->toDateTimeString(),
            'updated_at' => $utcTimestamp->toDateTimeString(),
        ]);

        return Transaction::withoutLegalWindow()->findOrFail($tx->id);
    }

    public function test_cairo_15_month_fiscal_cutoff_boundaries_and_april_rollover(): void
    {
        $client = $this->createClient();

        // 1. Late 2025 (23:30 Cairo on Dec 31, 2025 -> 21:30 UTC Dec 31, 2025)
        $tx2025 = $this->insertTransactionAt($client, 500.0, 'received', '2025-12-31 23:30:00');
        // 2. Early Jan 1, 2026 Cairo (00:15 Cairo on Jan 1, 2026 -> 22:15 UTC Dec 31, 2025)
        $tx2026 = $this->insertTransactionAt($client, 300.0, 'received', '2026-01-01 00:15:00');
        // 3. Feb 2027
        $tx2027 = $this->insertTransactionAt($client, 200.0, 'received', '2027-02-10 14:00:00');

        // Scenario A: Nov 1, 2026 Cairo -> cutoff is Jan 1, 2026 00:00 Cairo
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00', 'Africa/Cairo')->setTimezone('UTC'));
        $this->assertSame('2026-01-01', LegalWindow::cutoffDateString());
        $this->assertSame(2026, LegalWindow::earliestVisibleYear());

        $visibleIdsNov2026 = Transaction::pluck('id')->all();
        $this->assertNotContains($tx2025->id, $visibleIdsNov2026);
        $this->assertContains($tx2026->id, $visibleIdsNov2026);

        // Scenario B: Feb 15, 2027 Cairo (month <= 3 grace window: Jan 1, 2026 through Mar 31, 2027 = 15 months)
        Carbon::setTestNow(Carbon::parse('2027-02-15 12:00:00', 'Africa/Cairo')->setTimezone('UTC'));
        $this->assertSame('2026-01-01', LegalWindow::cutoffDateString());
        $this->assertSame(2026, LegalWindow::earliestVisibleYear());

        $visibleIdsFeb2027 = Transaction::pluck('id')->all();
        $this->assertNotContains($tx2025->id, $visibleIdsFeb2027);
        $this->assertContains($tx2026->id, $visibleIdsFeb2027);
        $this->assertContains($tx2027->id, $visibleIdsFeb2027);

        // Scenario C: Apr 1, 2027 00:05 Cairo (month >= 4 -> cutoff rolls forward to Jan 1, 2027 00:00 Cairo)
        Carbon::setTestNow(Carbon::parse('2027-04-01 00:05:00', 'Africa/Cairo')->setTimezone('UTC'));
        $this->assertSame('2027-01-01', LegalWindow::cutoffDateString());
        $this->assertSame(2027, LegalWindow::earliestVisibleYear());

        $visibleIdsApr2027 = Transaction::pluck('id')->all();
        $this->assertSame([$tx2027->id], $visibleIdsApr2027);
    }

    public function test_all_time_wallet_balance_running_balance_after_and_admin_net_kpi_remain_exact(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00', 'Africa/Cairo')->setTimezone('UTC'));

        $admin = $this->createAdmin();
        $client = $this->createClient();
        $project = Project::forceCreate([
            'user_id' => $client->id,
            'project_name' => 'Legacy Retainer Project',
            'status' => 'In Progress',
        ]);

        // Historical +1000 deposit in 2024 (outside legal window) + -200 charge in 2026 (inside legal window)
        $tx2024 = $this->insertTransactionAt($client, 1000.0, 'received', '2024-05-10 11:00:00', $project->id);
        $tx2026 = $this->insertTransactionAt($client, -200.0, 'used', '2026-06-15 15:00:00', $project->id);

        // Only 1 row is visible in Eloquent, while 2 rows exist in the ledger
        $this->assertSame(1, Transaction::count());
        $this->assertSame(2, Transaction::withoutLegalWindow()->count());
        $this->assertNull(Transaction::find($tx2024->id));

        // Running BALANCE AFTER on the 2026 row MUST include the hidden 2024 +1000 deposit (= 800.0)
        $visibleRow = Transaction::firstOrFail();
        $this->assertSame($tx2026->id, $visibleRow->id);
        $this->assertEquals(800.0, (float) $visibleRow->balance());

        // BalancesHelper & BalanceService recalculations preserve true 800.0 wallet & project balances
        BalancesHelper::UpdateBalance($client, $project);
        $client->refresh();
        $project->refresh();
        $this->assertEquals(800.0, (float) $client->user_balance);
        $this->assertEquals(800.0, (float) $project->project_balance);

        // BalanceService (which sums credits - debits as positive magnitudes when stored positive, or let's verify ledger_balance)
        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Transactions/Income')
                ->where('transactions.total', 1)
                ->where('summary.total_count', 1)
                ->where('summary.positive_business_amount', 0)
                ->where('summary.negative_business_amount', 200)
                ->where('summary.total_business_amount', 800)
            );

        // Filtering by client shows true ledger_balance = 800 and balance = 800
        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['user' => $client->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transactions.total', 1)
                ->where('filteredUser.balance', 800)
                ->where('filteredUser.ledger_balance', 800)
                ->where('summary.total_business_amount', 800)
            );

        // Client portal /financial/transactions shows only 2026 row with exact balance_before=1000 & balance_after=800
        $this->actingAs($client)
            ->get(route('financial.transactions'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Financial/Transactions')
                ->where('wallet.balance', 800)
                ->where('transactions.total', 1)
                ->where('transactions.data.0.balance_before', 1000)
                ->where('transactions.data.0.balance_after', 800)
            );

        // Passing an ancient ?from_date=2020-01-01 cannot bypass the 15-month hard lock
        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['from_date' => '2020-01-01']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transactions.total', 1)
            );
    }

    public function test_hidden_pre_cutoff_unpaid_invoices_return_404_but_still_lock_wallet_and_auto_pay(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00', 'Africa/Cairo')->setTimezone('UTC'));

        $admin = $this->createAdmin();
        $client = $this->createClient();

        // Give client 800 USD wallet balance
        $this->insertTransactionAt($client, 800.0, 'received', '2024-03-01 10:00:00');
        BalancesHelper::UpdateBalance($client);
        $client->refresh();

        // Create an unpaid 2024 invoice for 150 USD
        $oldInvoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => 1,
            'status' => 'unpaid',
            'job_status' => 'done',
            'is_suspended' => false,
        ]);
        InvoiceItem::forceCreate([
            'invoice_id' => $oldInvoice->id,
            'item_title' => '2024 Hosting Renewal',
            'amount' => 150,
            'qty' => 1,
        ]);
        DB::table('invoices')->where('id', $oldInvoice->id)->update([
            'final_total' => 150,
            'unpaid' => 150,
            'created_at' => '2024-06-01 10:00:00',
            'updated_at' => '2024-06-01 10:00:00',
        ]);

        // Create an unpaid 2026 invoice for 100 USD
        $newInvoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => 1,
            'status' => 'unpaid',
            'job_status' => 'done',
            'is_suspended' => false,
        ]);
        InvoiceItem::forceCreate([
            'invoice_id' => $newInvoice->id,
            'item_title' => '2026 Cloud Upgrade',
            'amount' => 100,
            'qty' => 1,
        ]);
        DB::table('invoices')->where('id', $newInvoice->id)->update([
            'final_total' => 100,
            'unpaid' => 100,
            'created_at' => '2026-07-01 10:00:00',
            'updated_at' => '2026-07-01 10:00:00',
        ]);

        // UI & Eloquent queries only see the 2026 invoice; direct URL to 2024 invoice returns 404 even for Admin
        $this->assertSame(1, Invoice::count());
        $this->actingAs($admin)->get(route('admin.invoices.show', $oldInvoice->id))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.invoices.show', $newInvoice->id))->assertOk();
        $this->actingAs($client)->get("/app/invoices/{$oldInvoice->id}")->assertNotFound();

        // Internal debt, locked balance, and available balance still include the 2024 unpaid invoice (150 + 100 = 250)
        $this->assertTrue($client->hasUnpaidInvoices());
        $this->assertEquals(250.0, (float) $client->totalUnpaidAmount());
        $this->assertEquals(250.0, (float) $client->locked_balance());
        $this->assertEquals(550.0, (float) $client->available_balance());

        // Auto-settlement settles both the hidden 2024 invoice and the 2026 invoice cleanly
        $client->try_pay_unpaid_invoices();
        $this->assertSame('paid', Invoice::withoutLegalWindow()->findOrFail($oldInvoice->id)->status);
        $this->assertSame('paid', Invoice::withoutLegalWindow()->findOrFail($newInvoice->id)->status);
    }

    public function test_operational_models_year_pickers_and_notifications_obey_window_while_master_and_pdpl_records_stay_intact(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00', 'Africa/Cairo')->setTimezone('UTC'));

        $admin = $this->createAdmin();
        $client = $this->createClient();

        // Master Project from 2023 must remain visible
        $project = Project::forceCreate([
            'user_id' => $client->id,
            'project_name' => '2023 Master Account Project',
            'status' => 'In Progress',
        ]);
        DB::table('projects')->where('id', $project->id)->update([
            'created_at' => '2023-02-01 09:00:00',
            'updated_at' => '2023-02-01 09:00:00',
        ]);
        $this->assertNotNull(Project::find($project->id));

        // CostTransactions: 2024 vs 2026
        $cost2024 = CostTransaction::forceCreate([
            'user_id' => $admin->id,
            'currency_id' => 1,
            'amount' => 90,
            'business_amount' => 90,
            'reason' => '2024 Server Cost',
        ]);
        DB::table('cost_transactions')->where('id', $cost2024->id)->update(['created_at' => '2024-04-10 10:00:00']);

        $cost2026 = CostTransaction::forceCreate([
            'user_id' => $admin->id,
            'currency_id' => 1,
            'amount' => 120,
            'business_amount' => 120,
            'reason' => '2026 Server Cost',
        ]);
        DB::table('cost_transactions')->where('id', $cost2026->id)->update(['created_at' => '2026-04-10 10:00:00']);
        $this->assertSame(1, CostTransaction::count());

        // Tickets & Tasks: 2024 vs 2026
        $ticket2024 = Ticket::forceCreate([
            'user_id' => $client->id,
            'ticket_subject' => 'Old 2024 Ticket',
            'ticket_message' => 'Old message',
            'ticket_status' => 'open',
            'priority' => 'normal',
        ]);
        DB::table('tickets')->where('id', $ticket2024->id)->update(['created_at' => '2024-08-01 10:00:00']);

        $ticket2026 = Ticket::forceCreate([
            'user_id' => $client->id,
            'ticket_subject' => 'Current 2026 Ticket',
            'ticket_message' => 'New message',
            'ticket_status' => 'open',
            'priority' => 'normal',
        ]);
        DB::table('tickets')->where('id', $ticket2026->id)->update(['created_at' => '2026-08-01 10:00:00']);
        $this->assertSame(1, Ticket::count());

        $task2024 = Task::forceCreate([
            'task_name' => 'Old 2024 Task',
            'user_id' => $admin->id,
            'priority' => 'normal',
            'archived' => 0,
        ]);
        DB::table('tasks')->where('id', $task2024->id)->update(['created_at' => '2024-08-01 10:00:00']);

        $task2026 = Task::forceCreate([
            'task_name' => 'Current 2026 Task',
            'user_id' => $admin->id,
            'priority' => 'normal',
            'archived' => 0,
        ]);
        DB::table('tasks')->where('id', $task2026->id)->update(['created_at' => '2026-08-01 10:00:00']);
        $this->assertSame(1, Task::count());

        // Database notifications on User::notifications(): 2024 vs 2026
        DB::table('notifications')->insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\SystemAlert',
                'notifiable_type' => User::class,
                'notifiable_id' => $client->id,
                'data' => json_encode(['title' => '2024 Notice']),
                'created_at' => '2024-09-01 10:00:00',
                'updated_at' => '2024-09-01 10:00:00',
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\SystemAlert',
                'notifiable_type' => User::class,
                'notifiable_id' => $client->id,
                'data' => json_encode(['title' => '2026 Notice']),
                'created_at' => '2026-09-01 10:00:00',
                'updated_at' => '2026-09-01 10:00:00',
            ],
        ]);
        $this->assertSame(1, $client->notifications()->count());

        // Business Income & Costs year pickers exclude pre-cutoff years
        $this->actingAs($admin)
            ->get(route('admin.costs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.available_years', [2026])
            );

        $this->actingAs($admin)
            ->get(route('admin.income.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.available_years', [2026])
            );

        // PDPL 3-year statutory consent log from 2024 remains intact
        $consent = UserConsentLog::create([
            'user_id' => $client->id,
            'consent_type' => ConsentType::DirectMarketingEmail,
            'action' => ConsentAction::Granted,
            'policy_version' => 'v1.0-2024',
            'ip_address' => '197.55.0.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => Carbon::parse('2024-05-01 10:00:00', 'UTC'),
        ]);
        $this->assertNotNull(UserConsentLog::find($consent->id));
        $this->assertTrue($client->hasGrantedConsent(ConsentType::DirectMarketingEmail));

        // Encrypted server-side backup still archives all historical rows (withoutGlobalScopes)
        $this->insertTransactionAt($client, 500.0, 'received', '2024-02-01 10:00:00');
        $this->insertTransactionAt($client, 250.0, 'received', '2026-02-01 10:00:00');

        Storage::fake('local');
        $exitCode = $this->artisan('privacy:encrypted-backup', ['--disk' => 'local']);
        $this->assertSame(0, is_int($exitCode) ? $exitCode : 0);
        $files = Storage::disk('local')->files('encrypted-backups');
        $this->assertCount(1, $files);

        $decryptedJson = gzdecode(base64_decode(Crypt::decryptString((string) Storage::disk('local')->get($files[0]))));
        $snapshot = json_decode((string) $decryptedJson, true);
        $this->assertCount(2, $snapshot['tables']['transactions']);
    }
}
