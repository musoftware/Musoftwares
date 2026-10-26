<?php

namespace Tests\Feature\Client;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyTier;
use App\Models\User;
use App\Models\UserSubscription;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The client home answers: what do I owe and where do I pay, what needs me, and how do I rank
 * this month (without seeing who the other clients are). Plus the settings page and saved language.
 */
class ClientHomeTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->currency = Currency::firstOrCreate(['currency' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']);
        LoyaltyTier::firstOrCreate(['slug' => 'bronze'], ['name' => 'Bronze', 'min_lifetime_points' => 0, 'order_index' => 1, 'is_active' => true]);
        LoyaltyTier::firstOrCreate(['slug' => 'silver'], ['name' => 'Silver', 'min_lifetime_points' => 200, 'order_index' => 2, 'is_active' => true]);
    }

    private function client(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['currency_id' => $this->currency->id, 'onboarding_completed' => true, 'profile_completion_percentage' => 100]);
        $user->assignRole('client');

        return $user;
    }

    private function invoice(User $user, float $amount, ?string $dueDate): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_id' => $this->currency->id,
            'paid' => 0,
            'status' => 'unpaid',
            'is_suspended' => false,
            'due_date' => $dueDate,
        ]);
        if ($amount > 0) {
            InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Work', 'amount' => $amount]);
        }
        $invoice->updateCachedTotal(); // totals come from the items

        return $invoice;
    }

    private function earn(User $user, int $points, $when = null): void
    {
        LoyaltyPointTransaction::create(['user_id' => $user->id, 'event_type' => 'invoice_paid', 'points' => $points, 'created_at' => $when ?? now(), 'updated_at' => $when ?? now()]);
    }

    public function test_client_with_nothing_due_is_all_set(): void
    {
        $this->actingAs($this->client())->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Dashboard')
            ->where('home.money.unpaid_count', 0)
            ->where('home.money.next_invoice', null)
            ->where('home.attention', [])
        );
    }

    public function test_amount_due_next_invoice_and_pay_link(): void
    {
        $user = $this->client();
        $overdue = $this->invoice($user, 300, now()->subDays(3)->toDateString());
        $this->invoice($user, 200, now()->addDays(10)->toDateString());
        $this->invoice($user, 0, null); // fully paid amount: not listed

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('home.money.total_due', 500)
            ->where('home.money.unpaid_count', 2)
            ->where('home.money.overdue_count', 1)
            ->where('home.money.next_invoice.number', (string) $overdue->id)
            ->where('home.money.next_invoice.is_overdue', true)
            ->where('home.money.next_invoice.pay_url', route('billing.invoices.pay', $overdue->uuid))
            ->where('home.attention.0.type', 'invoice_overdue')
            ->where('home.attention.1.type', 'invoice_due')
        );
    }

    public function test_invoices_page_lists_what_to_pay_oldest_first_and_keeps_history_apart(): void
    {
        $user = $this->client();
        $later = $this->invoice($user, 200, now()->addDays(10)->toDateString());
        $overdue = $this->invoice($user, 300, now()->subDays(3)->toDateString());
        $paid = $this->invoice($user, 0, null);
        $paid->forceFill(['status' => 'paid'])->saveQuietly();

        $this->actingAs($user)->get('/billing/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Billing/Invoices')
            ->has('unpaid_invoices', 2)
            ->where('unpaid_invoices.0.id', $overdue->id)
            ->where('unpaid_invoices.0.is_overdue', true)
            ->where('unpaid_invoices.0.title', 'Work')
            ->where('unpaid_invoices.1.id', $later->id)
            ->where('unpaid_invoices.1.is_overdue', false)
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $paid->id)
        );
    }

    public function test_subscription_ending_soon_needs_attention_unless_it_renews(): void
    {
        $user = $this->client();
        UserSubscription::create(['user_id' => $user->id, 'object' => 'gold-saver', 'status' => 'active', 'started_at' => now()->subMonth(), 'expires_at' => now()->addDays(5), 'auto_renew' => false]);
        UserSubscription::create(['user_id' => $user->id, 'object' => 'crm', 'status' => 'active', 'started_at' => now()->subMonth(), 'expires_at' => now()->addDays(3), 'auto_renew' => true]);

        $this->actingAs($user)->get('/dashboard?lang=en')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('home.attention', 1)
            ->where('home.attention.0.type', 'renewal')
            ->where('home.attention.0.name', 'Gold Saver')
            ->where('home.attention.0.days', 5)
            ->where('home.money.next_renewal.name', 'CRM')
        );
    }

    public function test_monthly_ranking_is_anonymous_and_ignores_staff_and_last_month(): void
    {
        $me = $this->client(['name' => 'Sara Me']);
        $top = $this->client(['name' => 'Secret Top Client', 'email' => 'secret-top@example.com']);
        $third = $this->client(['name' => 'Hidden Third']);
        $admin = $this->client(['name' => 'Staff Person']);
        $admin->assignRole('admin');

        $this->earn($top, 500);
        $this->earn($me, 120);
        $this->earn($third, 50);
        $this->earn($admin, 9999);
        $this->earn($third, 5000, now()->subMonthNoOverflow()->startOfMonth()); // last month does not count

        // accounts may get sign-up points too, so compare against what the ledger really holds this month
        $monthly = fn (User $u) => (int) LoyaltyPointTransaction::where('user_id', $u->id)->where('points', '>', 0)->where('created_at', '>=', now()->startOfMonth())->sum('points');

        $response = $this->actingAs($me)->get('/dashboard');
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('home.rewards.monthly.participants', 3)
            ->where('home.rewards.monthly.me.position', 2)
            ->where('home.rewards.monthly.me.points', $monthly($me))
            ->where('home.rewards.monthly.top.0.points', $monthly($top))
            ->where('home.rewards.monthly.top.0.is_me', false)
            ->where('home.rewards.monthly.top.1.is_me', true)
            ->missing('home.rewards.monthly.top.0.user_id')
            ->missing('home.rewards.monthly.top.0.name')
        );

        $ranking = json_encode($response->viewData('page')['props']['home']['rewards']['monthly']);
        foreach (['Secret Top Client', 'secret-top@example.com', 'Hidden Third', 'Staff Person', (string) $top->id.'"'] as $leak) {
            $this->assertStringNotContainsString($leak, $ranking);
        }

        $this->actingAs($me)->get('/loyalty')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('monthlyRanking.me.position', 2)
            ->has('monthlyRanking.top', 3)
        );
    }

    public function test_users_who_never_picked_a_language_are_not_switched_by_the_column_default(): void
    {
        $user = $this->client(); // users.lang defaults to 'ar', users.locale is empty

        $this->actingAs($user)->get('/settings')->assertInertia(fn (Assert $page) => $page->where('currentLang', config('app.locale') === 'ar' ? 'ar' : 'en'));
    }

    public function test_settings_page_and_language_is_saved_on_the_account(): void
    {
        $user = $this->client(['lang' => 'en']);

        $this->actingAs($user)->get('/settings?lang=ar')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Settings/Index')
            ->where('currentLang', 'ar')
        );
        $this->assertSame('ar', $user->fresh()->locale);
        $this->assertSame('ar', $user->fresh()->lang);

        // a new session (another device) opens in the saved language
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/settings')->assertInertia(fn (Assert $page) => $page->where('currentLang', 'ar'));
    }
}
