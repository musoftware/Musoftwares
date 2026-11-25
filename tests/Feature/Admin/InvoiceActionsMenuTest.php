<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $usd = Currency::firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'string_format' => '$:value', 'exchange_rate' => 1.0]);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'client']);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
        $client = User::factory()->create(['currency_id' => $usd->id, 'onboarding_completed' => true]);
        $client->assignRole('client');

        $this->invoice = Invoice::create(['user_id' => $client->id, 'currency_id' => $usd->id, 'status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $this->invoice->id, 'item_title' => 'Work', 'amount' => 50, 'qty' => 1]);
    }

    public function test_an_unpaid_invoice_with_items_can_be_cancelled(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $this->invoice))->assertRedirect();

        $this->assertSame('cancelled', $this->invoice->fresh()->status);
    }

    public function test_deleting_from_the_invoice_page_goes_to_the_list(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invoices.bulk-action'), ['action' => 'delete', 'invoices' => [$this->invoice->id], 'from' => 'show'])
            ->assertRedirect(route('admin.invoices.index'));

        $this->assertNull(Invoice::find($this->invoice->id));
    }

    public function test_deleting_from_the_list_goes_back(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.invoices.unpaid'))
            ->post(route('admin.invoices.bulk-action'), ['action' => 'delete', 'invoices' => [$this->invoice->id]])
            ->assertRedirect(route('admin.invoices.unpaid'));
    }
}
