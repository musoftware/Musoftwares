<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeUsersRequest;
use App\Models\User;
use App\Models\UserEmail;
use App\Services\UserMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class UserMergeController extends Controller
{
    public function __construct(protected UserMergeService $mergeService) {}

    public function select(Request $request, User $user): InertiaResponse
    {
        $filter = trim((string) $request->query('search', ''));

        $suggestions = collect();
        if ($filter !== '') {
            $suggestions = User::query()
                ->where('id', '!=', $user->id)
                ->whereNull('merged_into_user_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($filter) {
                    $needle = strtolower($filter);
                    if (is_numeric($filter)) {
                        $q->where('id', (int) $filter);
                    }
                    $q->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]);
                })
                ->orderBy('id')
                ->limit(20)
                ->with('roles')
                ->get(['id', 'name', 'email', 'email_verified_at']);
        }

        $recentlyMerged = User::onlyTrashed()
            ->whereNotNull('merged_into_user_id')
            ->where('merged_into_user_id', $user->id)
            ->orderByDesc('deleted_at')
            ->limit(5)
            ->get(['id', 'name', 'email', 'deleted_at']);

        return Inertia::render('Admin/Users/MergeSelect', [
            'survivor' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'search' => $filter,
            'suggestions' => $suggestions->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->getRoleNames()->first(),
                'email_verified' => (bool) $u->email_verified_at,
            ])->values(),
            'recently_merged' => $recentlyMerged->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'merged_at' => $u->deleted_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function preview(Request $request, User $user): InertiaResponse
    {
        $request->validate([
            'duplicate_ids' => ['required', 'array', 'min:1'],
            'duplicate_ids.*' => ['integer', 'distinct', 'different:'.$user->id, 'exists:users,id'],
        ]);

        $duplicateIds = array_values(array_unique(array_map('intval', $request->input('duplicate_ids'))));

        $duplicates = User::withTrashed()
            ->whereIn('id', $duplicateIds)
            ->get(['id', 'name', 'email', 'deleted_at']);

        $report = [];
        foreach ($duplicates as $dup) {
            $report[] = $this->mergeService->preview($user->id, $dup->id);
        }

        return Inertia::render('Admin/Users/Merge', [
            'survivor' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'duplicates' => $duplicates->map(fn (User $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'email' => $d->email,
                'merged' => (bool) $d->deleted_at,
            ])->values()->all(),
            'reports' => $report,
        ]);
    }

    public function confirm(MergeUsersRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $duplicateIds = $request->duplicateIds();
        $resolutions = (array) $request->validated('resolutions', []);

        try {
            $outcomes = $this->mergeService->mergeMany(
                $user->id,
                $duplicateIds,
                $resolutions,
                (int) (Auth::id() ?? 0)
            );
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Merge failed: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', 'Merge failed: '.$e->getMessage());
        }

        $aliases = UserEmail::where('user_id', $user->id)
            ->where('source', UserEmail::SOURCE_MERGE)
            ->orderByDesc('id')
            ->limit(count($duplicateIds))
            ->pluck('email')
            ->all();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Accounts merged.',
                'outcomes' => $outcomes,
                'aliases' => $aliases,
            ]);
        }

        $count = count($duplicateIds);

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', "{$count} account(s) merged into #{$user->id}.");
    }
}
