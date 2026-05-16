<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function impersonate(Request $request, $id)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403, 'Only admins can impersonate users.');
        }

        if (Auth::id() == $id) {
            return back();
        }

        session()->put('impersonator_id', Auth::id());
        session()->put('impersonate', $id);

        Auth::loginUsingId($id);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating(Request $request)
    {
        if (session()->has('impersonator_id')) {
            $impersonatorId = session()->get('impersonator_id');
            session()->forget('impersonator_id');
            session()->forget('impersonate');

            Auth::loginUsingId($impersonatorId);
        }

        return redirect()->route('dashboard');
    }
}
