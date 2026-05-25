<?php

namespace Modules\Booking\app\Features\QueueManagement\Services;

use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueLog;
use Illuminate\Support\Facades\DB;

class BookingQueueService
{
    protected $tokenGenerator;
    protected $limitsService;

    public function __construct(QueueTokenGenerator $tokenGenerator, BookingQueueLimitsService $limitsService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->limitsService = $limitsService;
    }

    /**
     * Check in a customer to a specific queue.
     * Works for both walk-ins and scheduled appointments.
     */
    public function checkIn(BookingQueue $queue, array $data): BookingQueueEntry
    {
        // Enforce SaaS Walk-in Limits
        $this->limitsService->enforce('max_daily_queue_entries');

        return DB::transaction(function () use ($queue, $data) {
            
            $tokenData = $this->tokenGenerator->generateNextToken($queue);

            $entry = BookingQueueEntry::create([
                'tenant_id' => $queue->tenant_id,
                'queue_id' => $queue->id,
                'booking_id' => $data['booking_id'] ?? null,
                'walkin_name' => $data['walkin_name'] ?? null,
                'walkin_phone' => $data['walkin_phone'] ?? null,
                'priority_level' => $data['priority_level'] ?? 0,
                
                'token_number' => $tokenData['token_number'],
                'sequence_number' => $tokenData['sequence_number'],
                
                'status' => 'waiting',
                'checked_in_at' => now(),
            ]);

            BookingQueueLog::create([
                'tenant_id' => $queue->tenant_id,
                'queue_entry_id' => $entry->id,
                'action' => 'created',
                'performed_by_user_id' => auth()->id(),
            ]);

            $this->limitsService->increaseUsage('max_daily_queue_entries');

            event(new \Modules\Booking\app\Features\QueueManagement\Events\QueueEntryCreated($entry));

            return $entry;
        });
    }
}
