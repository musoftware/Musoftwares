<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tools\AdminResellerService;
use App\Http\Requests\Admin\Tools\StoreResellerRequest;
use App\Http\Requests\Admin\Tools\UpdateResellerRequest;
use App\Http\Requests\Admin\Tools\AdjustResellerBalanceRequest;
use App\Http\Resources\Tools\ToolResellerResource;
use App\Http\Resources\Tools\ToolResellerUserResource;
use App\Http\Resources\Tools\ToolResellerTransactionResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\ToolReseller;
use Modules\Tools\Models\ToolResellerUser;

class AdminResellerController extends Controller
{
    public function __construct(
        protected AdminResellerService $adminResellerService
    ) {}
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $resellers = ToolReseller::with('user')
            ->withCount(['resellerUsers', 'resellerUsers as active_users_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['resellerUsers as sharing_flagged_count' => fn ($q) => $q->where('is_sharing_flagged', true)])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Resellers/Index', [
            'resellers' => clone (new ToolResellerResource($resellers))->resolve(),
            'meta'      => $resellers->toArray(),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): Response
    {
        return Inertia::render('Admin/Resellers/Create');
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(StoreResellerRequest $request): RedirectResponse
    {
        try {
            $this->adminResellerService->createReseller($request->validated());
        } catch (\Exception $e) {
            return back()->withErrors(['user_id' => $e->getMessage()]);
        }

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Reseller account created successfully.');
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(int $id): Response
    {
        $reseller = ToolReseller::with('user')->findOrFail($id);

        $subUsers = ToolResellerUser::with('user')
            ->where('reseller_id', $id)
            ->latest()
            ->paginate(20, ['*'], 'users_page');

        $transactions = $reseller->transactions()
            ->with('user')
            ->latest()
            ->paginate(20, ['*'], 'tx_page');

        $sharingFlagged = ToolResellerUser::with('user')
            ->where('reseller_id', $id)
            ->where('is_sharing_flagged', true)
            ->get();

        return Inertia::render('Admin/Resellers/Show', [
            'reseller'       => clone (new ToolResellerResource($reseller))->resolve(),
            'subUsers'       => $subUsers->through(fn ($u) => clone (new ToolResellerUserResource($u))->resolve()),
            'transactions'   => $transactions->through(fn ($t) => clone (new ToolResellerTransactionResource($t))->resolve()),
            'sharingFlagged' => ToolResellerUserResource::collection($sharingFlagged)->resolve(),
            'iframeUrl'      => url("/reseller/{$reseller->token}"),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(UpdateResellerRequest $request, int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);

        $this->adminResellerService->updateReseller($reseller, $request->validated());

        return back()->with('success', 'Reseller updated.');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);
        $this->adminResellerService->deleteReseller($reseller);

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Reseller deactivated.');
    }

    // ─── Adjust Balance ───────────────────────────────────────────────────────

    public function adjustBalance(AdjustResellerBalanceRequest $request, int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);

        $this->adminResellerService->adjustBalance($reseller, $request->validated());

        return back()->with('success', 'Balance adjusted successfully.');
    }

    // ─── Sub-User: Suspend / Activate ─────────────────────────────────────────

    public function suspendUser(int $resellerId, int $userId): RedirectResponse
    {
        $this->adminResellerService->suspendUser($resellerId, $userId);

        return back()->with('success', 'Sub-user suspended.');
    }

    public function activateUser(int $resellerId, int $userId): RedirectResponse
    {
        $this->adminResellerService->activateUser($resellerId, $userId);

        return back()->with('success', 'Sub-user re-activated.');
    }

    // ─── Sharing: Clear Flag ──────────────────────────────────────────────────

    public function clearSharingFlag(int $resellerId, int $userId): RedirectResponse
    {
        $this->adminResellerService->clearSharingFlag($resellerId, $userId);

        return back()->with('success', 'Sharing flag cleared. Sessions reset. User can log in again.');
    }

    public function toggleSharingCheck(Request $request, int $resellerId, int $userId): RedirectResponse
    {
        $state = $this->adminResellerService->toggleSharingCheck($resellerId, $userId);
        return back()->with('success', "Sharing detection {$state} for this user.");
    }

    // ─── Search Users (for Create form autocomplete) ──────────────────────────

    public function searchUsers(Request $request)
    {
        $q = $request->get('q', '');
        $users = User::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(15)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
