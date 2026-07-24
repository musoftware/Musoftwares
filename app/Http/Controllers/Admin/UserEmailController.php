<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\DestroyUserEmailRequest;
use App\Http\Requests\Admin\User\StoreUserEmailRequest;
use App\Models\User;
use App\Models\UserEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserEmailController extends Controller
{
    public function index(Request $request, User $user): InertiaResponse
    {
        $emails = $user->emails()
            ->orderBy('email')
            ->get(['id', 'email', 'verified_at', 'source', 'created_at']);

        $primary = [
            'email' => $user->email,
            'verified_at' => $user->email_verified_at,
        ];

        $filter = trim((string) $request->query('search', ''));
        $suggestions = collect();
        if ($filter !== '') {
            $suggestions = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($q) use ($filter) {
                    $needle = strtolower($filter);
                    $q->whereRaw('LOWER(email) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]);
                })
                ->orderBy('id')
                ->limit(20)
                ->get(['id', 'name', 'email']);
        }

        return Inertia::render('Admin/Users/UserEmails', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'primary' => $primary,
            'emails' => $emails->map(fn (UserEmail $e) => [
                'id' => $e->id,
                'email' => $e->email,
                'verified' => (bool) $e->isVerified(),
                'verified_at' => $e->verified_at?->toIso8601String(),
                'source' => $e->source,
                'created_at' => $e->created_at?->toIso8601String(),
            ])->values(),
            'suggestions' => $suggestions->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])->values(),
            'search' => $filter,
        ]);
    }

    public function store(StoreUserEmailRequest $request, User $user): RedirectResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $verified = $request->boolean('verified_at');

        UserEmail::create([
            'user_id' => $user->id,
            'email' => $email,
            'verified_at' => $verified ? now() : null,
            'source' => UserEmail::SOURCE_ADMIN,
            'added_by_user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.users.emails.index', $user->id)
            ->with('success', __('admin.alias_added', ['email' => $email]));
    }

    public function destroy(DestroyUserEmailRequest $request, User $user, UserEmail $email): RedirectResponse
    {
        abort_unless((int) $email->user_id === (int) $user->id, 404);

        $email->delete();

        return redirect()
            ->route('admin.users.emails.index', $user->id)
            ->with('success', __('admin.alias_removed'));
    }

    public function verify(Request $request, User $user, UserEmail $email): RedirectResponse
    {
        abort_unless((int) $email->user_id === (int) $user->id, 404);

        $email->forceFill(['verified_at' => now()])->save();

        return redirect()
            ->route('admin.users.emails.index', $user->id)
            ->with('success', __('admin.alias_verified'));
    }

    public function makePrimary(Request $request, User $user, UserEmail $email): RedirectResponse
    {
        abort_unless((int) $email->user_id === (int) $user->id, 404);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $email) {
            $oldPrimaryEmail = $user->email;
            $oldVerifiedAt = $user->email_verified_at;

            $newPrimaryEmail = $email->email;
            $newVerifiedAt = $email->verified_at ?? now();

            $user->forceFill([
                'email' => $newPrimaryEmail,
                'email_verified_at' => $newVerifiedAt,
            ])->save();

            $email->forceFill([
                'email' => $oldPrimaryEmail,
                'verified_at' => $oldVerifiedAt,
            ])->save();
        });

        return redirect()
            ->route('admin.users.emails.index', $user->id)
            ->with('success', __('admin.primary_email_updated'));
    }

}
