<?php

namespace Modules\PasswordSync\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PasswordSync\app\Models\PasswordVault;
use Illuminate\Support\Facades\Auth;

class PasswordSyncController extends Controller
{
    /**
     * API: Get the vault for the authenticated user
     */
    public function getVault(Request $request)
    {
        $vault = PasswordVault::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['encrypted_data' => null]
        );

        return response()->json([
            'success' => true,
            'vault' => $vault->encrypted_data
        ]);
    }

    /**
     * API: Update the vault for the authenticated user
     */
    public function updateVault(Request $request)
    {
        $request->validate([
            'vault' => 'required'
        ]);

        $vault = PasswordVault::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        $vault->encrypted_data = $request->vault;
        $vault->save();

        return response()->json([
            'success' => true,
            'message' => 'Vault updated successfully'
        ]);
    }

    /**
     * WEB: Auth success page that passes token to extension
     */
    public function authSuccess(Request $request)
    {
        // Require user to be logged in via web guard
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        
        // Delete old extension tokens or just create a new one
        // For simplicity, we create a new token. You might want to manage tokens.
        $token = $user->createToken('PasswordSyncExtension')->plainTextToken;

        return view('passwordsync::auth_success', compact('token', 'user'));
    }
}
