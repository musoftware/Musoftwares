<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\UserEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Client/Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'error' => session('error'),
            'hasUnpaidInvoices' => $request->user()->hasUnpaidInvoices(),
            'emails' => $request->user()->emails()->get(['id', 'email', 'verified_at', 'source']),
        ]);
    }

    /**
     * Add a secondary email to user profile.
     */
    public function storeEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->input('email')));

        if (User::findForLogin($email)) {
            return back()->withErrors(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        UserEmail::create([
            'user_id' => $request->user()->id,
            'email' => $email,
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_SELF,
            'added_by_user_id' => $request->user()->id,
        ]);

        return Redirect::route('profile.edit')->with('status', __('general.email_added_successfully'));
    }

    /**
     * Delete a secondary email from user profile.
     */
    public function destroyEmail(Request $request, UserEmail $userEmail): RedirectResponse
    {
        if ($userEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        $userEmail->delete();

        return Redirect::route('profile.edit')->with('status', __('general.email_deleted_successfully'));
    }

    /**
     * Update a secondary email address.
     */
    public function updateEmail(Request $request, UserEmail $userEmail): RedirectResponse
    {
        if ($userEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $newEmail = strtolower(trim($request->input('email')));

        if ($newEmail !== strtolower($userEmail->email) && User::findForLogin($newEmail)) {
            return back()->withErrors(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        $userEmail->forceFill([
            'email' => $newEmail,
            'verified_at' => now(),
        ])->save();

        return Redirect::route('profile.edit')->with('status', __('general.email_updated_successfully'));
    }

    /**
     * Set a secondary email as the user's primary email.
     */
    public function makePrimaryEmail(Request $request, UserEmail $userEmail): RedirectResponse
    {
        if ($userEmail->user_id !== $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($request, $userEmail) {
            $user = $request->user();
            $oldPrimaryEmail = $user->email;
            $oldVerifiedAt = $user->email_verified_at;

            $newPrimaryEmail = $userEmail->email;
            $newVerifiedAt = $userEmail->verified_at ?? now();

            $user->forceFill([
                'email' => $newPrimaryEmail,
                'email_verified_at' => $newVerifiedAt,
            ])->save();

            $userEmail->forceFill([
                'email' => $oldPrimaryEmail,
                'verified_at' => $oldVerifiedAt,
            ])->save();
        });

        return Redirect::route('profile.edit')->with('status', __('general.email_made_primary_successfully'));
    }

    /**
     * Update the user's preferences (e.g., dashboard style).
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'enable_3d_dashboard' => ['required', 'boolean'],
            'default_ai_model' => ['nullable', 'string', 'in:openai,gemini'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_model' => ['nullable', 'string', 'max:255'],
            'gemini_api' => ['nullable', 'string', 'max:255'],
            'gemini_model' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->forceFill([
            'enable_3d_dashboard' => $request->boolean('enable_3d_dashboard'),
            'default_ai_model' => $request->input('default_ai_model'),
            'openai_api_key' => $request->input('openai_api_key'),
            'openai_model' => $request->input('openai_model') ?: 'gpt-4o-mini',
            'gemini_api' => $request->input('gemini_api'),
            'gemini_model' => $request->input('gemini_model') ?: 'gemini-2.0-flash',
        ])->save();

        return Redirect::route('profile.edit')->with('status', __('general.preferences_updated_successfully'));
    }

    /**
     * Update the user's workspace settings.
     */
    public function updateWorkspaceSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $settings = $user->workspace_settings ?? [];

        if ($request->has('hide_values')) {
            $settings['hide_values'] = $request->boolean('hide_values');
        }

        $user->workspace_settings = $settings;
        $user->save();

        return back();
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (session()->has('impersonator_id')) {
            abort(403, __('general.impersonators_cannot_perform_this_action'));
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->hasUnpaidInvoices()) {
            return back()->withErrors([
                'password' => __('general.cannot_delete_account_with_unpaid_invoices'),
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
