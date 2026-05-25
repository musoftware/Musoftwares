<?php

namespace Modules\Booking\app\Features\QueueManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueDisplay;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;

class QueueDisplayController extends Controller
{
    /**
     * Public endpoint to get the current TV payload data.
     * This is requested by the waiting room TV using its secret display_key.
     */
    public function getDisplayPayload($displayKey)
    {
        // Unauthenticated route, so we look up by the secret unguessable hash key
        $display = BookingQueueDisplay::where('display_key', $displayKey)
            ->where('is_active', true)
            ->firstOrFail();

        $queue = BookingQueue::with(['entries' => function ($query) {
            // Show only what the public TV needs to see
            $query->whereIn('status', ['waiting', 'called'])
                  ->orderBy('status') // 'called' will usually flash
                  ->orderBy('sequence_number', 'asc')
                  ->select(['id', 'queue_id', 'token_number', 'status', 'walkin_name', 'called_at']); 
                  // Deliberately hiding phone numbers and tenant IDs from public JSON
        }])->findOrFail($display->queue_id);

        return response()->json([
            'display_name' => $display->tv_name,
            'theme' => $display->theme_settings,
            'queue_name' => $queue->name,
            'active_entries' => $queue->entries,
            'websocket_channel' => "public.queue.{$queue->id}.displays",
        ]);
    }
}
