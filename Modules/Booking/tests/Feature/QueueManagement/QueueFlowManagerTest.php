<?php

namespace Modules\Booking\tests\Feature\QueueManagement;

use Tests\TestCase;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;
use Modules\Booking\app\Features\QueueManagement\Services\QueueFlowManager;
use Modules\Booking\app\Features\QueueManagement\Events\QueueTokenCalled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class QueueFlowManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calls_highest_priority_oldest_waiting_entry()
    {
        Event::fake();

        $queue = BookingQueue::forceCreate([
            'tenant_id' => 1,
            'name' => 'General',
        ]);

        // Regular walk-in
        BookingQueueEntry::forceCreate([
            'tenant_id' => 1,
            'queue_id' => $queue->id,
            'token_number' => 'A-001',
            'sequence_number' => 1,
            'status' => 'waiting',
            'priority_level' => 0,
        ]);

        // VIP (Should be called first despite higher sequence)
        $vipEntry = BookingQueueEntry::forceCreate([
            'tenant_id' => 1,
            'queue_id' => $queue->id,
            'token_number' => 'A-002',
            'sequence_number' => 2,
            'status' => 'waiting',
            'priority_level' => 10,
        ]);

        $manager = new QueueFlowManager();
        $called = $manager->callNext($queue);

        $this->assertNotNull($called);
        $this->assertEquals($vipEntry->id, $called->id);
        $this->assertEquals('called', $called->status);

        Event::assertDispatched(QueueTokenCalled::class, function ($e) use ($vipEntry) {
            return $e->entry->id === $vipEntry->id;
        });
    }

    public function test_it_cannot_serve_an_uncalled_entry()
    {
        $queue = BookingQueue::forceCreate([
            'tenant_id' => 1,
            'name' => 'General',
        ]);

        $entry = BookingQueueEntry::forceCreate([
            'tenant_id' => 1,
            'queue_id' => $queue->id,
            'token_number' => 'A-001',
            'sequence_number' => 1,
            'status' => 'waiting',
        ]);

        $manager = new QueueFlowManager();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Entry must be 'called' before it can be 'serving'.");
        
        $manager->startServing($entry);
    }
}
