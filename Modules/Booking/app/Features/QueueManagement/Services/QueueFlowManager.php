<?php

namespace Modules\Booking\app\Features\QueueManagement\Services;

use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueLog;
use Exception;
use Illuminate\Support\Facades\DB;

class QueueFlowManager
{
    /**
     * Call the NEXT logical patient in the queue.
     * Ordered by priority_level (DESC), then sequence_number (ASC).
     */
    public function callNext(BookingQueue $queue): ?BookingQueueEntry
    {
        return DB::transaction(function () use ($queue) {
            $nextEntry = BookingQueueEntry::where('queue_id', $queue->id)
                ->where('status', 'waiting')
                ->orderBy('priority_level', 'desc')
                ->orderBy('sequence_number', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$nextEntry) {
                return null;
            }

            $this->changeStatus($nextEntry, 'called');

            return $nextEntry;
        });
    }

    /**
     * Manually call a specific entry out of order.
     */
    public function callSpecific(BookingQueueEntry $entry): BookingQueueEntry
    {
        if (!in_array($entry->status, ['waiting', 'skipped'])) {
            throw new Exception("Cannot call an entry with status: {$entry->status}");
        }

        return $this->changeStatus($entry, 'called');
    }

    /**
     * Mark an entry as serving.
     */
    public function startServing(BookingQueueEntry $entry): BookingQueueEntry
    {
        if ($entry->status !== 'called') {
            throw new Exception("Entry must be 'called' before it can be 'serving'.");
        }

        return $this->changeStatus($entry, 'serving');
    }

    /**
     * Mark an entry as completed.
     */
    public function complete(BookingQueueEntry $entry): BookingQueueEntry
    {
        return $this->changeStatus($entry, 'completed');
    }

    /**
     * Skip an entry (e.g. they didn't show up when called).
     */
    public function skip(BookingQueueEntry $entry): BookingQueueEntry
    {
        return $this->changeStatus($entry, 'skipped');
    }

    protected function changeStatus(BookingQueueEntry $entry, string $status): BookingQueueEntry
    {
        $entry->status = $status;
        
        if ($status === 'called') {
            $entry->called_at = now();
            event(new \Modules\Booking\app\Features\QueueManagement\Events\QueueTokenCalled($entry));
        } elseif ($status === 'serving') {
            $entry->serving_at = now();
        } elseif ($status === 'completed') {
            $entry->completed_at = now();
            event(new \Modules\Booking\app\Features\QueueManagement\Events\QueueEntryCompleted($entry));
        }

        $entry->save();

        BookingQueueLog::create([
            'tenant_id' => $entry->tenant_id,
            'queue_entry_id' => $entry->id,
            'action' => "status_changed_to_{$status}",
            'performed_by_user_id' => auth()->check() ? auth()->id() : null,
        ]);

        // TODO: Fire realtime update broadcast for queue state

        return $entry;
    }
}
