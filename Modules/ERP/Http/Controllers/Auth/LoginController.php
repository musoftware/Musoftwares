<?php

namespace Modules\ERP\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the login view for ERP Team Members.
     */
    public function showLoginForm()
    {
        return Inertia::render('ERP/Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::guard('erp_team')->attempt($credentials, $remember)) {
            $member = Auth::guard('erp_team')->user();
            
            if ($member->status !== 'active') {
                Auth::guard('erp_team')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = $member->status === 'pending' 
                    ? __('erp.account_pending_acceptance') 
                    : __('erp.account_suspended');
                    
                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            $member->update(['last_login_at' => now()]);

            $request->session()->regenerate();

            return redirect()->route('erp.dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('erp_team')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('erp.login');
    }
}
