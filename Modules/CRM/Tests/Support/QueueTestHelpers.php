<?php

namespace Modules\CRM\Tests\Support;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

trait QueueTestHelpers
{
    /**
     * Fake the queue and specifically assert a job was pushed
     * mimicking an afterCommit behavior if necessary.
     */
    protected function fakeQueueAndAssertPushed(string $jobClass, callable $callback = null): void
    {
        Queue::fake([$jobClass]);

        // In a real DB transaction, afterCommit jobs are queued by the dispatcher only on commit.
        // We can listen to the event or just rely on Laravel's Queue::fake() which captures them.
        
        if ($callback) {
            Queue::assertPushed($jobClass, $callback);
        } else {
            Queue::assertPushed($jobClass);
        }
    }

    /**
     * Helper to simulate firing a webhook concurrently to test idempotency locks.
     * Note: Pure concurrency is hard in PHPUnit without pcntl_fork.
     * This fakes the effect by manipulating Redis locks directly or 
     * testing the action synchronously multiple times.
     */
    protected function simulateConcurrentWebhook(callable $webhookAction, int $times = 3): void
    {
        // For testing idempotency, we run the exact same payload multiple times synchronously.
        // The first run should lock and process, subsequent runs should drop.
        
        $initialQueueCount = count(Queue::pushed('whatsapp-incoming') ?? []);
        
        for ($i = 0; $i < $times; $i++) {
            $webhookAction();
        }
        
        // Assert that the job was only pushed ONCE despite being fired multiple times
        $finalQueueCount = count(Queue::pushed('whatsapp-incoming') ?? []);
        
        $this->assertEquals(
            $initialQueueCount + 1, 
            $finalQueueCount, 
            "Webhook was processed multiple times despite idempotency protections!"
        );
    }
}
