<?php

namespace Modules\Booking\app\Features\GroupSessions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Modules\Booking\app\Features\GroupSessions\Models\GroupParticipant;
use Modules\Booking\app\Features\GroupSessions\Services\GroupSessionService;
use Modules\Booking\app\Features\GroupSessions\Events\GroupParticipantRemoved;
use Modules\Booking\app\Features\GroupSessions\Jobs\PromoteWaitlistJob;
use Modules\Booking\app\Features\GroupSessions\Services\BookingGroupSessionLimitsService;

class GroupSessionController extends Controller
{
    protected $sessionService;
    protected $limitsService;

    public function __construct(GroupSessionService $sessionService, BookingGroupSessionLimitsService $limitsService)
    {
        $this->sessionService = $sessionService;
        $this->limitsService = $limitsService;
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $sessions = GroupSession::withCount(['participants' => function($q) {
            $q->where('status', 'confirmed');
        }])->where('tenant_id', auth()->user()->tenant_id)->get();
        
        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        if (!$this->limitsService->canCreateSession(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Feature locked. Upgrade to unlock Group Sessions.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
            'max_capacity' => 'required|integer|min:1',
            'min_capacity' => 'integer|min:1',
            'price' => 'numeric',
            'resource_id' => 'integer|nullable',
        ]);

        $session = GroupSession::create($validated);

        return response()->json($session, 201);
    }

    public function join(Request $request, int $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer'
        ]);

        $result = $this->sessionService->joinSession($id, $validated['customer_id']);

        return response()->json($result);
    }

    public function cancel(Request $request, int $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer'
        ]);

        $participant = GroupParticipant::where('group_session_id', $id)
            ->where('customer_id', $validated['customer_id'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $participant->update(['status' => 'cancelled']);

        event(new GroupParticipantRemoved($participant));
        
        // Dispatch job to promote waitlist automatically
        PromoteWaitlistJob::dispatch($id);

        return response()->json(['message' => 'Participant cancelled. Waitlist promotion triggered.']);
    }
}
