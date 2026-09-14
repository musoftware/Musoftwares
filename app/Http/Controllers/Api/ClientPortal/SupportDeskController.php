<?php

namespace App\Http\Controllers\Api\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\LoyaltyService;
use App\Services\TierPriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportDeskController extends Controller
{
    public function __construct(
        protected TierPriorityService $tierPriorityService,
        protected LoyaltyService $loyaltyService
    ) {}

    /**
     * List user's tickets sorted by algorithmic priority score.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->orderBy('priority_score', 'desc')
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $tickets,
        ]);
    }

    /**
     * Open a self-service ticket with VIP programmatic priority routing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_subject' => ['required', 'string', 'max:255'],
            'ticket_message' => ['required', 'string', 'min:10'],
            'urgency'        => ['nullable', 'in:low,normal,high,critical'],
        ]);

        $user = $request->user();
        $urgency = $validated['urgency'] ?? 'normal';

        // Ensure user tier is synced with lifetime spend
        $this->tierPriorityService->syncUserTier($user);

        // Calculate score
        $score = $this->tierPriorityService->calculateTicketScore($user, $urgency);

        $priorityLevel = match (true) {
            in_array($user->tier, ['enterprise', 'pro'], true) || in_array($urgency, ['high', 'critical'], true) => 'high',
            default => 'medium',
        };

        $ticket = Ticket::create([
            'user_id'          => $user->id,
            'ticket_subject'   => $validated['ticket_subject'],
            'ticket_message'   => $validated['ticket_message'],
            'ticket_status'    => 'open',
            'priority'         => $priorityLevel,
            'priority_score'   => $score,
            'is_self_service'  => true,
        ]);

        // Award 15 points automatically for self-service ticket creation
        $pointTxn = $this->loyaltyService->awardPoints(
            $user,
            'ticket_self_opened',
            $ticket,
            ['ticket_id' => $ticket->id, 'subject' => $ticket->ticket_subject]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket opened with VIP priority ranking. 15 loyalty points awarded.',
            'data' => [
                'ticket' => $ticket,
                'priority_score' => $score,
                'points_awarded' => $pointTxn ? 15 : 0,
            ],
        ], 201);
    }
}
