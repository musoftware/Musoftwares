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
}
