<?php

namespace Modules\Booking\app\Features\QueueManagement\Services;

use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueTokenGenerator
{
    /**
     * Generates a unique, strictly sequential token for a queue using pessimistic locking.
     * 
     * @return array [string $token_number, int $sequence_number]
     */
    public function generateNextToken(BookingQueue $queue): array
    {
        return DB::transaction(function () use ($queue) {
            // Lock the queue row exclusively to prevent race conditions during concurrent walk-ins
            $lockedQueue = BookingQueue::where('id', $queue->id)->lockForUpdate()->first();

            $today = Carbon::today();

            // If the sequence date is null or older than today, reset the sequence to 1
            if (!$lockedQueue->current_sequence_date || !$lockedQueue->current_sequence_date->isSameDay($today)) {
                $lockedQueue->current_sequence_date = $today;
                $lockedQueue->current_sequence_number = 1;
            } else {
                $lockedQueue->current_sequence_number += 1;
            }

            $lockedQueue->save();

            $sequence = $lockedQueue->current_sequence_number;
            $prefix = $lockedQueue->prefix ? trim($lockedQueue->prefix) . '-' : '';
            
            $tokenString = $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT); // e.g. CL-001

            return [
                'token_number' => $tokenString,
                'sequence_number' => $sequence,
            ];
        });
    }
}
