<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserIdChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** The owner gives an account another, unused ID. */
class UserIdChangeController extends Controller
{
    public function __construct(private readonly UserIdChange $ids) {}

    public function show(Request $request, User $user): Response
    {
        $wanted = (int) $request->query('to');

        return Inertia::render('Admin/Users/ChangeId', [
            'client' => $user->only(['id', 'name', 'email']),
            'wanted' => $wanted ?: null,
            'free' => $wanted > 0 ? $this->ids->isFree($wanted) : null,
            'moves' => $this->ids->preview((int) $user->id),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['new_id' => ['required', 'integer', 'min:1', 'max:4294967295']]);
        $newId = (int) $data['new_id'];
        if (! $this->ids->isFree($newId)) {
            return back()->withErrors(['new_id' => __('admin.user_id_taken')]);
        }
        $own = $request->user()->id === $user->id;
        $this->ids->change($user, $newId, (int) $request->user()->id);
        // the admin changed their own ID: they stay signed in under the new one
        if ($own) {
            Auth::guard('web')->loginUsingId($newId);
            $request->session()->regenerate();
        }

        return redirect()->route('admin.users.show', $newId)->with('success', __('admin.user_id_changed', ['id' => $newId]));
    }
}
