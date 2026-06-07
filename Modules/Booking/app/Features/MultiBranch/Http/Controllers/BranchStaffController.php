<?php

namespace Modules\Booking\app\Features\MultiBranch\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\MultiBranch\Repositories\BookingBranchRepository;
use Modules\Booking\app\Features\MultiBranch\Services\BranchManagerService;
use Modules\Booking\app\Features\MultiBranch\Http\Resources\BookingBranchResource;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BranchStaffController extends Controller implements HasMiddleware
{
    protected $repository;
    protected $managerService;

    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!feature('booking-multi-branch')) {
                    return response()->json(['message' => 'Feature locked. Upgrade to enable Multi Branch.'], 403);
                }
                return $next($request);
            }),
        ];
    }

    public function __construct(BookingBranchRepository $repository, BranchManagerService $managerService)
    {
        $this->repository = $repository;
        $this->managerService = $managerService;
    }

    public function index($branchId)
    {
        $branch = $this->repository->find($branchId);
        $this->authorize('view', $branch);

        $branch->load('users');
        return BookingBranchResource::make($branch);
    }

    public function store(Request $request, $branchId)
    {
        $branch = $this->repository->find($branchId);
        $this->authorize('assignStaff', $branch);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|in:manager,staff',
        ]);

        $this->managerService->assignStaff($branch, $request->user_id, $request->role ?? 'staff');

        return response()->json(['message' => 'Staff assigned successfully.']);
    }

    public function destroy($branchId, $userId)
    {
        $branch = $this->repository->find($branchId);
        $this->authorize('assignStaff', $branch);

        $this->managerService->removeStaff($branch, $userId);

        return response()->json(['message' => 'Staff removed successfully.']);
    }
}
