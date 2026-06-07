<?php

namespace Modules\Booking\app\Features\MultiBranch\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\MultiBranch\Repositories\BookingBranchRepository;
use Modules\Booking\app\Features\MultiBranch\Http\Requests\StoreBookingBranchRequest;
use Modules\Booking\app\Features\MultiBranch\Http\Requests\UpdateBookingBranchRequest;
use Modules\Booking\app\Features\MultiBranch\Events\BookingBranchCreated;
use Modules\Booking\app\Features\MultiBranch\Events\BookingBranchUpdated;
use Modules\Booking\app\Features\MultiBranch\Events\BookingBranchDeleted;
use Modules\Booking\app\Features\MultiBranch\Http\Resources\BookingBranchResource;
use Modules\Booking\app\Features\MultiBranch\Models\BookingBranch;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BookingBranchController extends Controller implements HasMiddleware
{
    protected $repository;

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

    public function __construct(BookingBranchRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $this->authorize('viewAny', BookingBranch::class);
        $branches = $this->repository->all();
        return BookingBranchResource::collection($branches);
    }

    public function store(StoreBookingBranchRequest $request)
    {
        $this->authorize('create', BookingBranch::class);
        // Enforce maximum branch limits if needed here (e.g. if limit is defined in saas.php)

        $branch = $this->repository->create($request->validated());
        
        event(new BookingBranchCreated($branch));

        return BookingBranchResource::make($branch)->response()->setStatusCode(201);
    }

    public function show($id)
    {
        $branch = $this->repository->find($id);
        $this->authorize('view', $branch);
        
        return BookingBranchResource::make($branch);
    }

    public function update(UpdateBookingBranchRequest $request, $id)
    {
        $branch = $this->repository->find($id);
        $this->authorize('update', $branch);
        
        $branch = $this->repository->update($branch, $request->validated());
        
        event(new BookingBranchUpdated($branch));

        return BookingBranchResource::make($branch);
    }

    public function destroy($id)
    {
        $branch = $this->repository->find($id);
        $this->authorize('delete', $branch);
        
        $this->repository->delete($branch);
        
        event(new BookingBranchDeleted($branch));

        return response()->json(null, 204);
    }
}
