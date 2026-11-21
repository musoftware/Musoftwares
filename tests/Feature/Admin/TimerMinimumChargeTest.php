<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\TimerMinimumCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimerMinimumChargeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private InvoiceItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'string_format' => '$:value', 'exchange_rate' => 1.0]);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'client']);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $client = User::factory()->create(['currency_id' => $currency->id, 'onboarding_completed' => true]);
        $client->assignRole('client');

        $invoice = Invoice::create(['user_id' => $client->id, 'currency_id' => $currency->id, 'status' => 'unpaid']);
        $this->item = InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => 'Timer', 'item_type' => 'timer', 'amount' => 0]);

        AdminSettings::SetValue(TimerMinimumCharge::CURRENCY_KEY, 'USD');
    }

    private function saveSession(float $amount): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.timer-details.store', $this->item->id), [
            'reason' => 'Small fix',
            'sessions' => [['start_date' => now()->subMinutes(6)->toDateTimeString(), 'end_date' => now()->toDateTimeString(), 'amount' => $amount]],
        ])->assertRedirect();
    }

    public function test_a_small_task_is_raised_to_the_minimum(): void
    {
        AdminSettings::SetValue(TimerMinimumCharge::AMOUNT_KEY, '5');

        $this->saveSession(0.9);

        $this->assertEqualsWithDelta(5.0, (float) $this->item->timers()->sum('amount'), 0.001);
    }

    public function test_a_task_above_the_minimum_is_not_changed(): void
    {
        AdminSettings::SetValue(TimerMinimumCharge::AMOUNT_KEY, '5');

        $this->saveSession(12);

        $this->assertEqualsWithDelta(12.0, (float) $this->item->timers()->sum('amount'), 0.001);
    }

    public function test_earlier_sessions_count_toward_the_minimum(): void
    {
        AdminSettings::SetValue(TimerMinimumCharge::AMOUNT_KEY, '5');

        $this->saveSession(3);
        $this->saveSession(4);

        $this->assertEqualsWithDelta(9.0, (float) $this->item->timers()->sum('amount'), 0.001);
    }

    public function test_zero_turns_the_minimum_off(): void
    {
        AdminSettings::SetValue(TimerMinimumCharge::AMOUNT_KEY, '0');

        $this->saveSession(0.9);

        $this->assertEqualsWithDelta(0.9, (float) $this->item->timers()->sum('amount'), 0.001);
    }

    public function test_the_page_shows_the_minimum(): void
    {
        AdminSettings::SetValue(TimerMinimumCharge::AMOUNT_KEY, '5');

        $this->actingAs($this->admin)->get(route('admin.invoices.timer-details', $this->item->id))
            ->assertInertia(fn ($page) => $page->where('minimum_charge', 5));
    }
}
