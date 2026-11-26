<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceSplitItemsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Invoice $invoice;

    /** @var array<int, InvoiceItem> */
    private array $items;

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

        $this->invoice = Invoice::create(['user_id' => $client->id, 'currency_id' => $currency->id, 'status' => 'unpaid']);
        $this->items = collect([10, 20, 30])->map(fn (int $amount) => InvoiceItem::create([
            'invoice_id' => $this->invoice->id, 'item_title' => "Row {$amount}", 'item_type' => 'simple', 'amount' => $amount, 'qty' => 1,
        ]))->all();
    }

    private function split(array $itemIds)
    {
        return $this->actingAs($this->admin)->post(route('admin.invoices.split-items', $this->invoice->id), ['item_ids' => $itemIds]);
    }

    public function test_selected_rows_move_to_a_new_invoice_and_both_totals_update(): void
    {
        $response = $this->split([$this->items[1]->id, $this->items[2]->id]);

        $newInvoice = Invoice::where('id', '!=', $this->invoice->id)->sole();
        $response->assertRedirect(route('admin.invoices.show', $newInvoice->id));

        $this->assertSame($this->invoice->user_id, $newInvoice->user_id);
        $this->assertSame('unpaid', $newInvoice->fresh()->status);
        $this->assertEqualsCanonicalizing([$this->items[1]->id, $this->items[2]->id], $newInvoice->items()->pluck('id')->all());
        $this->assertEqualsWithDelta(50.0, (float) $newInvoice->fresh()->final_total, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $this->invoice->fresh()->final_total, 0.001);
    }

    public function test_moving_every_row_is_refused(): void
    {
        $this->split(collect($this->items)->pluck('id')->all())->assertSessionHas('error');

        $this->assertSame(1, Invoice::count());
        $this->assertSame(3, $this->invoice->items()->count());
    }

    public function test_a_row_from_another_invoice_is_refused(): void
    {
        $other = Invoice::create(['user_id' => $this->invoice->user_id, 'currency_id' => $this->invoice->currency_id, 'status' => 'unpaid']);
        $foreign = InvoiceItem::create(['invoice_id' => $other->id, 'item_title' => 'Other', 'item_type' => 'simple', 'amount' => 5, 'qty' => 1]);

        $this->split([$foreign->id])->assertSessionHas('error');

        $this->assertSame($other->id, $foreign->fresh()->invoice_id);
    }

    public function test_a_paid_invoice_cannot_be_split(): void
    {
        $this->invoice->update(['status' => 'paid']);

        $this->split([$this->items[0]->id])->assertSessionHas('error');

        $this->assertSame(3, $this->invoice->items()->count());
    }
}
