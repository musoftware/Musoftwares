<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Todo;
use App\Models\InvoiceItemTimer;

class AdminTaskControllerTest extends TestCase
{
    /**
     * Test the storeClientTodo method to create and bill a task.
     *
     * @return void
     */
    public function test_admin_can_store_and_bill_client_todo()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = User::factory()->create(['currency' => '1', 'booking_rate' => 0]);
        $client->assignRole('client');

        // Add some balance so the client can be billed
        if ($client->available_balance() < 5000) {
            $client->add_balance(5000, 'Test balance', 'earned', $client->currency);
        }

        $start = now('Africa/Cairo')->addDays(1)->startOfHour();
        $end = clone $start;
        $end->addHours(2); // 2 hours duration

        $payload = [
            'title' => 'Test Focus Task',
            'start_at' => $start->toDateTimeString(),
            'end_at' => $end->toDateTimeString(),
            'description' => 'Test focus task description.',
        ];

        $response = $this->actingAs($admin)->post("/admin/tasks/client-tasks/{$client->id}/todos", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('message', 'Scheduled task created and billed successfully!');

        // Assert the Todo was created
        $this->assertDatabaseHas('todos', [
            'user_id' => $client->id,
            'title' => 'Test Focus Task',
            'is_paid' => 1,
        ]);

        // Assert Invoice was created
        $this->assertDatabaseHas('invoices', [
            'user_id' => $client->id,
            'status' => 'unpaid',
        ]);

        // Assert Invoice Item Timer was created with correct currency
        $this->assertDatabaseHas('invoice_item_timers', [
            'user_id' => $client->id,
            'currency_id' => $client->currency,
        ]);
    }
}
