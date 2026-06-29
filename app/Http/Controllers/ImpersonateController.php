<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function impersonate(Request $request, $id)
    {
        $actorId = Auth::id();

        if (! $request->user()->isAdmin()) {
            abort(403, __('general.only_admins_can_impersonate_users'));
        }

        if ($actorId == $id) {
            return back();
        }

        $target = User::find($id);
        if (! $target) {
            abort(404);
        }

        // Block impersonating another admin (no privilege escalation chain).
        if ($target->isAdmin() && $target->id !== $actorId) {
            return back()->withErrors(['error' => __('errors.cannot_impersonate_admin')]);
        }

        // Refuse to impersonate a blocked account.
        if (($target->account_status ?? null) === 'blocked') {
            return back()->withErrors(['error' => __('errors.cannot_impersonate_blocked_user')]);
        }

        $startedAt = now();

        session()->put('impersonator_id', $actorId);
        session()->put('impersonate', $id);
        session()->put('impersonation_started_at', $startedAt->toIso8601String());

        app(AdminAuditService::class)->record(
            'user.impersonate',
            $target,
            [
                'actor_user_id' => $actorId,
                'started_at' => $startedAt->toIso8601String(),
            ],
            AdminAuditLog::SEVERITY_CRITICAL
        );

        Auth::loginUsingId($id);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating(Request $request)
    {
        if (session()->has('impersonator_id')) {
            $impersonatorId = session()->get('impersonator_id');
            $impersonatedId = Auth::id();
            $startedAt = session()->get('impersonation_started_at');

            session()->forget([
                'impersonator_id',
                'impersonate',
                'impersonating_user_id',
                'impersonated_by',
                'impersonation_started_at',
            ]);

            if ($impersonatedId && $impersonatorId) {
                app(AdminAuditService::class)->recordRaw(
                    'user.stop_impersonation',
                    'User',
                    (string) $impersonatedId,
                    [
                        'actor_user_id' => $impersonatorId,
                        'started_at' => $startedAt,
                        'ended_at' => now()->toIso8601String(),
                    ],
                    AdminAuditLog::SEVERITY_CRITICAL,
                    $impersonatorId
                );
            }

            Auth::loginUsingId($impersonatorId);
        }

        return redirect()->route('dashboard');
    }
}
