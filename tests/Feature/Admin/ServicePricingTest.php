<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Notifications\TaskRatingRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServicePricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Currency $usd;

    private InvoiceItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'string_format' => '$:value', 'exchange_rate' => 1.0]);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'client']);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        // The client's own rate: 100 USD an hour.
        $this->client = User::factory()->create([
            'currency_id' => $this->usd->id,
            'hour_rate' => 100,
            'hour_rate_currency_id' => $this->usd->id,
            'enable_custom_hour_rate' => true,
            'onboarding_completed' => true,
        ]);
        $this->client->assignRole('client');

        $this->item = $this->newTask();
    }

    private function newTask(): InvoiceItem
    {
        $invoice = Invoice::create(['user_id' => $this->client->id, 'currency_id' => $this->usd->id, 'status' => 'unpaid']);

        return InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Add backoffice', 'item_type' => 'timer', 'amount' => 0]);
    }

    /** Saves one 6-minute session worth 10 USD of real time. */
    private function saveSixMinutes(array $details): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.timer-details.store', $this->item->id), [
            'reason' => 'Add backoffice',
            'sessions' => [['start_date' => '2026-11-21 13:29:00', 'end_date' => '2026-11-21 13:35:00', 'amount' => 10]],
            ...$details,
        ])->assertRedirect();
    }

    private function billed(): float
    {
        return (float) $this->item->timers()->sum('amount');
    }

    public function test_the_price_uses_market_time_not_real_time(): void
    {
        $this->saveSixMinutes(['market_minutes' => 120]);

        $this->assertEqualsWithDelta(200.0, $this->billed(), 0.001);
    }

    public function test_starting_soon_after_the_request_adds_a_bonus(): void
    {
        $this->saveSixMinutes(['market_minutes' => 120, 'requested_at' => '2026-11-21 13:19:00']);

        $this->assertEqualsWithDelta(240.0, $this->billed(), 0.001);
    }

    public function test_this_clients_good_ratings_add_a_bonus(): void
    {
        $this->newTask()->forceFill(['client_rating' => 5])->save();

        $this->saveSixMinutes(['market_minutes' => 120]);

        $this->assertEqualsWithDelta(240.0, $this->billed(), 0.001);
    }

    public function test_another_clients_ratings_do_not_count(): void
    {
        $other = User::factory()->create(['currency_id' => $this->usd->id]);
        $invoice = Invoice::create(['user_id' => $other->id, 'currency_id' => $this->usd->id, 'status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Other', 'amount' => 0])->forceFill(['client_rating' => 5])->save();

        $this->saveSixMinutes(['market_minutes' => 120]);

        $this->assertEqualsWithDelta(200.0, $this->billed(), 0.001);
    }

    public function test_real_time_above_market_time_is_kept(): void
    {
        $this->saveSixMinutes(['market_minutes' => 3]);

        $this->assertEqualsWithDelta(10.0, $this->billed(), 0.001);
    }

    public function test_the_page_shows_the_breakdown(): void
    {
        $this->saveSixMinutes(['market_minutes' => 120, 'requested_at' => '2026-11-21 13:19:00']);

        $this->actingAs($this->admin)->get(route('admin.invoices.timer-details', $this->item->id))
            ->assertInertia(fn ($page) => $page
                ->has('timers', 1)
                ->where('service_quote.market_value', 200)
                ->where('service_quote.response_minutes', 10)
                ->where('service_quote.start_bonus_percent', 20)
                ->where('service_quote.floor', 240));
    }

    public function test_the_admin_emails_the_client_a_rating_link(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.invoices.timer-details.ask-rating', $this->item->id))->assertRedirect();

        $this->item->refresh();
        $this->assertSame(32, strlen($this->item->rating_token));
        $this->assertNotNull($this->item->rating_requested_at);
        Notification::assertSentTo($this->client, TaskRatingRequestNotification::class);
    }

    public function test_the_client_rates_the_task_from_the_link(): void
    {
        $this->item->forceFill(['rating_token' => str_repeat('a', 32)])->save();

        $this->get(route('task-rating.show', str_repeat('a', 32)))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Guest/TaskRating')->where('task.title', 'Add backoffice'));
        $this->post(route('task-rating.store', str_repeat('a', 32)), ['rating' => 5, 'note' => 'Fast and clean'])->assertRedirect();

        $this->item->refresh();
        $this->assertSame(5, $this->item->client_rating);
        $this->assertSame('Fast and clean', $this->item->client_rating_note);
    }

    public function test_a_wrong_rating_link_is_not_found(): void
    {
        $this->get(route('task-rating.show', str_repeat('b', 32)))->assertNotFound();
        $this->post(route('task-rating.store', str_repeat('b', 32)), ['rating' => 5])->assertNotFound();
    }
}
