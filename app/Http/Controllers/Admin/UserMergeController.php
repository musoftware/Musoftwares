<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeUsersRequest;
use App\Models\User;
use App\Services\UserMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class UserMergeController extends Controller
{
    public function __construct(protected UserMergeService $mergeService) {}

    public function preview(Request $request, User $user): InertiaResponse
    {
        $request->validate([
            'duplicate_id' => ['required', 'integer', 'different:' . $user->id, 'exists:users,id'],
        ]);

        $duplicate = User::findOrFail((int) $request->input('duplicate_id'));
        $preview = $this->mergeService->preview($user->id, $duplicate->id);

        return Inertia::render('Admin/Users/Merge', [
            'survivor'  => $preview['survivor'],
            'duplicate' => $preview['duplicate'],
            'conflicts' => $preview['field_conflicts'],
            'counts'    => $preview['child_counts'],
        ]);
    }

    public function confirm(MergeUsersRequest $request, User $user): RedirectResponse
    {
        $duplicateId = (int) $request->validated('duplicate_id');
        $resolutions = (array) $request->validated('resolutions', []);

        try {
            $this->mergeService->merge(
                $user->id,
                $duplicateId,
                $resolutions,
                (int) (Auth::id() ?? 0)
            );
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Merge failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', "User #{$duplicateId} merged into #{$user->id}.");
    }
}
