<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Services\MembershipService;
use App\Http\Resources\Admin\MembershipResource;
use App\Http\Requests\Admin\Membership\StoreMembershipRequest;
use App\Http\Requests\Admin\Membership\UpdateMembershipRequest;
use Inertia\Inertia;

class AdminMembershipController extends Controller
{
    public function __construct(
        protected MembershipService $membershipService
    ) {}

    public function index(Request $request)
    {
        $query = Membership::with(['users', 'programs.program']);

        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        if ($sort === 'users') {
            $query->withCount('users')->orderBy('users_count', $order);
        } else {
            $query->orderBy($sort, $order);
        }

        $memberships = $query->paginate(15)
                             ->withQueryString()
                             ->through(fn($m) => clone (new MembershipResource($m))->resolve());

        return Inertia::render('Admin/Memberships/Index', [
            'memberships' => $memberships,
            'filters'     => $request->only(['sort', 'order']),
        ]);
    }

    public function store(StoreMembershipRequest $request)
    {
        $this->membershipService->createMembership($request->validated());

        return redirect()->back()->with('success', 'Membership created successfully.');
    }

    public function update(UpdateMembershipRequest $request, Membership $membership)
    {
        $this->membershipService->updateMembership($membership, $request->validated());

        return redirect()->back()->with('success', 'Membership updated successfully.');
    }

    public function destroy(Membership $membership)
    {
        try {
            $this->membershipService->deleteMembership($membership);
            return redirect()->back()->with('success', 'Membership deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Methods like users(), software(), assignUser() can be added here following the same pattern
}
