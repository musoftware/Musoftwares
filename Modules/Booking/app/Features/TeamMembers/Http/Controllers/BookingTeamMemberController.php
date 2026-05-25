<?php

namespace Modules\Booking\app\Features\TeamMembers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;
use Modules\Booking\app\Features\TeamMembers\Services\TeamMemberManagerService;
use Modules\Booking\app\Features\TeamMembers\Services\TeamLimitsService;
use Modules\Booking\app\Features\TeamMembers\Http\Requests\StoreTeamMemberRequest;
use Modules\Booking\app\Features\TeamMembers\Http\Requests\UpdateTeamMemberRequest;
use Modules\Booking\app\Features\TeamMembers\Http\Resources\BookingTeamMemberResource;
use Modules\Booking\app\Features\TeamMembers\Events\TeamMemberAdded;
use Modules\Booking\app\Features\TeamMembers\Events\TeamMemberProfileUpdated;
use Modules\Booking\app\Features\TeamMembers\Events\TeamMemberDeactivated;
use Modules\Booking\app\Features\TeamMembers\Notifications\WelcomeToTheBookingTeam;

class BookingTeamMemberController extends Controller
{
    protected $managerService;
    protected $limitsService;

    public function __construct(TeamMemberManagerService $managerService, TeamLimitsService $limitsService)
    {
        $this->managerService = $managerService;
        $this->limitsService = $limitsService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', BookingTeamMember::class);
        
        $query = BookingTeamMember::with('user');
        
        if ($request->has('is_bookable')) {
            $query->where('is_bookable', $request->boolean('is_bookable'));
        }

        return BookingTeamMemberResource::collection($query->get());
    }

    public function store(StoreTeamMemberRequest $request)
    {
        $this->authorize('create', BookingTeamMember::class);
        
        $tenantId = auth()->user()->tenant_id;
        $currentCount = BookingTeamMember::where('tenant_id', $tenantId)->count();

        // Enforce limits via feature flag / saas config
        if (!$this->limitsService->canAddMore($tenantId, $currentCount)) {
            return response()->json([
                'message' => 'Feature locked. Upgrade your subscription to add more team members.'
            ], 403);
        }

        $profile = $this->managerService->createTeamMember($request->validated());
        
        event(new TeamMemberAdded($profile));
        
        // Notify the new user
        $profile->user->notify(new WelcomeToTheBookingTeam($profile));

        return BookingTeamMemberResource::make($profile->load('user'))->response()->setStatusCode(201);
    }

    public function show($id)
    {
        $profile = BookingTeamMember::with('user')->findOrFail($id);
        $this->authorize('view', $profile);
        
        return BookingTeamMemberResource::make($profile);
    }

    public function update(UpdateTeamMemberRequest $request, $id)
    {
        $profile = BookingTeamMember::findOrFail($id);
        $this->authorize('update', $profile);
        
        $profile->update($request->validated());
        
        if ($profile->wasChanged('is_bookable') && !$profile->is_bookable) {
            event(new TeamMemberDeactivated($profile));
        } else {
            event(new TeamMemberProfileUpdated($profile));
        }

        return BookingTeamMemberResource::make($profile->load('user'));
    }

    public function destroy($id)
    {
        $profile = BookingTeamMember::findOrFail($id);
        $this->authorize('delete', $profile);
        
        $profile->delete();
        
        event(new TeamMemberDeactivated($profile));

        return response()->json(null, 204);
    }
}
