<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTimer;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_invoice_merges_timer_items_correctly()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);

        $item1 = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Timer 1',
            'item_type' => 'timer',
            'amount' => 10,
            'qty' => 1,
            'currency' => 'USD'
        ]);

        $item2 = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_title' => 'Timer 2',
            'item_type' => 'timer',
            'amount' => 15,
            'qty' => 1,
            'currency' => 'USD'
        ]);

        $timer1 = InvoiceItemTimer::create([
            'invoice_item_id' => $item1->id,
            'amount' => 100,
            'date_start' => now()->subHours(2),
            'date_end' => now(),
            'currency_id' => 1,
            'user_id' => $user->id,
        ]);

        $timer2 = InvoiceItemTimer::create([
            'invoice_item_id' => $item2->id,
            'amount' => 150,
            'date_start' => now()->subHours(3),
            'date_end' => now(),
            'currency_id' => 1,
            'user_id' => $user->id,
        ]);

        // 2. Perform the Merge via InvoiceService
        $service = new InvoiceService();
        
        $data = [
            'deleted_items' => [$item1->id, $item2->id],
            'items' => [
                [
                    'item_title' => 'Timer 1 + Timer 2',
                    'amount' => '25', // combined hourly rate
                    'qty' => 1,
                    'item_type' => 'timer',
                    'merged_from' => [$item1->id, $item2->id],
                ]
            ],
            'cost_lines' => [],
            'deleted_cost_lines' => [],
        ];

        $service->updateInvoice($invoice, $data);

        // 3. Assertions
        
        // Old items should be deleted
        $this->assertDatabaseMissing('invoice_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('invoice_items', ['id' => $item2->id]);

        // New item should be created
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'item_title' => 'Timer 1 + Timer 2',
            'item_type' => 'timer',
        ]);

        $newItem = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('item_title', 'Timer 1 + Timer 2')
            ->first();

        $this->assertNotNull($newItem);

        // Timers should be reassigned to the new item
        $this->assertDatabaseHas('invoice_item_timers', [
            'id' => $timer1->id,
            'invoice_item_id' => $newItem->id,
        ]);

        $this->assertDatabaseHas('invoice_item_timers', [
            'id' => $timer2->id,
            'invoice_item_id' => $newItem->id,
        ]);
    }
}
