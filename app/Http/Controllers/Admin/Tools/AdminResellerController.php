<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\ToolReseller;
use Modules\Tools\Models\ToolResellerUser;

class AdminResellerController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $resellers = ToolReseller::with('user')
            ->withCount(['resellerUsers', 'resellerUsers as active_users_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['resellerUsers as sharing_flagged_count' => fn ($q) => $q->where('is_sharing_flagged', true)])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Resellers/Index', [
            'resellers' => $resellers->through(fn ($r) => $this->formatReseller($r)),
            'meta'      => $resellers->toArray(),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): Response
    {
        return Inertia::render('Admin/Resellers/Create');
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id'  => ['required', 'exists:users,id'],
            'name'     => ['required', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:10'],
            'notes'    => ['nullable', 'string'],
        ]);

        // Prevent duplicate resellers for the same user
        if (ToolReseller::where('user_id', $data['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'This user already has a reseller account.']);
        }

        ToolReseller::create([
            ...$data,
            'token'   => ToolReseller::generateToken(),
            'balance' => 0,
            'status'  => 'active',
        ]);

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
            'reseller'       => $this->formatReseller($reseller),
            'subUsers'       => $subUsers->through(fn ($u) => $this->formatSubUser($u)),
            'transactions'   => $transactions->through(fn ($t) => $this->formatTransaction($t)),
            'sharingFlagged' => $sharingFlagged->map(fn ($u) => $this->formatSubUser($u)),
            'iframeUrl'      => url("/reseller/{$reseller->token}"),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:10'],
            'status'   => ['required', 'in:active,suspended,inactive'],
            'notes'    => ['nullable', 'string'],
        ]);

        // Handle status change: suspend/activate sub-users accordingly
        if ($data['status'] === 'suspended' && $reseller->status !== 'suspended') {
            $reseller->suspend(auto: false);
        } elseif ($data['status'] === 'active' && $reseller->status !== 'active') {
            $reseller->activate();
        }

        $reseller->update($data);

        return back()->with('success', 'Reseller updated.');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);
        $reseller->update(['status' => 'inactive']);

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Reseller deactivated.');
    }

    // ─── Adjust Balance ───────────────────────────────────────────────────────

    public function adjustBalance(Request $request, int $id): RedirectResponse
    {
        $reseller = ToolReseller::findOrFail($id);

        $data = $request->validate([
            'type'        => ['required', 'in:top_up,manual_credit,manual_debit'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:512'],
        ]);

        if ($data['type'] === 'manual_debit') {
            $reseller->deductBalance(
                amount:      $data['amount'],
                description: $data['description'] ?? 'Manual debit by admin',
                type:        'manual_debit',
            );
        } else {
            $reseller->creditBalance(
                amount:      $data['amount'],
                description: $data['description'] ?? 'Admin top-up',
                type:        $data['type'],
            );
        }

        return back()->with('success', 'Balance adjusted successfully.');
    }

    // ─── Sub-User: Suspend / Activate ─────────────────────────────────────────

    public function suspendUser(int $resellerId, int $userId): RedirectResponse
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['status' => 'suspended']);

        return back()->with('success', 'Sub-user suspended.');
    }

    public function activateUser(int $resellerId, int $userId): RedirectResponse
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['status' => 'active']);

        return back()->with('success', 'Sub-user re-activated.');
    }

    // ─── Sharing: Clear Flag ──────────────────────────────────────────────────

    public function clearSharingFlag(int $resellerId, int $userId): RedirectResponse
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->clearSharingFlag();

        return back()->with('success', 'Sharing flag cleared. Sessions reset. User can log in again.');
    }

    public function toggleSharingCheck(Request $request, int $resellerId, int $userId): RedirectResponse
    {
        $ru = ToolResellerUser::where('reseller_id', $resellerId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $ru->update(['sharing_check_enabled' => !$ru->sharing_check_enabled]);

        $state = $ru->sharing_check_enabled ? 'disabled' : 'enabled';
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

    // ─── Formatters ───────────────────────────────────────────────────────────

    private function formatReseller(ToolReseller $r): array
    {
        return [
            'id'                  => $r->id,
            'name'                => $r->name,
            'token'               => $r->token,
            'balance'             => $r->balance,
            'currency'            => $r->currency,
            'status'              => $r->status,
            'notes'               => $r->notes,
            'user'                => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email] : null,
            'total_users'         => $r->reseller_users_count ?? 0,
            'active_users'        => $r->active_users_count ?? 0,
            'sharing_flagged'     => $r->sharing_flagged_count ?? 0,
            'created_at'          => $r->created_at?->toDateString(),
        ];
    }

    private function formatSubUser(ToolResellerUser $u): array
    {
        return [
            'id'                    => $u->id,
            'user_id'               => $u->user_id,
            'user'                  => $u->user ? ['id' => $u->user->id, 'name' => $u->user->name, 'email' => $u->user->email] : null,
            'status'                => $u->status,
            'sharing_check_enabled' => $u->sharing_check_enabled,
            'is_sharing_flagged'    => $u->is_sharing_flagged,
            'flagged_ips'           => $u->flagged_ips,
            'sharing_flagged_at'    => $u->sharing_flagged_at?->diffForHumans(),
            'joined_at'             => $u->joined_at?->toDateString(),
        ];
    }

    private function formatTransaction(object $t): array
    {
        return [
            'id'          => $t->id,
            'type'        => $t->type,
            'amount'      => $t->amount,
            'balance_after' => $t->balance_after,
            'currency'    => $t->currency,
            'description' => $t->description,
            'user'        => $t->user ? ['name' => $t->user->name, 'email' => $t->user->email] : null,
            'created_at'  => $t->created_at->format('M d, Y H:i'),
        ];
    }
}
