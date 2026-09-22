<?php

namespace App\Http\Controllers\Api\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\User;
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
            'project_id'     => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $user = $request->user();
        $urgency = $validated['urgency'] ?? 'normal';

        $projectId = null;
        if (! empty($validated['project_id'])) {
            $belongsToUser = \App\Models\Project::where('id', $validated['project_id'])
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('client_id', $user->id);
                })
                ->exists();
            if ($belongsToUser) {
                $projectId = (int) $validated['project_id'];
            }
        }

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
            'project_id'       => $projectId,
            'ticket_subject'   => $validated['ticket_subject'],
            'ticket_message'   => $validated['ticket_message'],
            'ticket_status'    => 'open',
            'priority'         => $priorityLevel,
            'priority_score'   => $score,
            'is_self_service'  => true,
        ]);

        $conversation = Conversation::create([
            'conversable_type' => Ticket::class,
            'conversable_id'   => $ticket->id,
            'type'             => 'support_ticket',
            'status'           => 'open',
        ]);

        $conversation->participants()->create([
            'user_id' => $user->id,
            'role'    => 'client',
        ]);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body'      => $validated['ticket_message'],
            'is_system' => false,
        ]);

        $adminUsers = rescue(fn () => User::role('admin')->get(), collect());
        foreach ($adminUsers as $admin) {
            if ($admin->id !== $user->id) {
                $conversation->participants()->firstOrCreate([
                    'user_id' => $admin->id,
                    'role'    => 'admin',
                ]);
            }
        }

        // Dispatch notifications (Client email, Admin emails, and FCM)
        \App\Services\TicketNotificationService::notifyOnTicketCreated($ticket);

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
