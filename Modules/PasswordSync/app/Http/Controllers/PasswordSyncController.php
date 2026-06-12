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
        $vault = PasswordVault::with('items')->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['encrypted_data' => null, 'salt' => null]
        );

        return response()->json([
            'success' => true,
            'vault' => $vault->encrypted_data,
            'salt' => $vault->salt,
            'items' => $vault->items->map(function ($item) {
                return [
                    'id' => $item->remote_id,
                    'data' => $item->encrypted_data
                ];
            })
        ]);
    }

    /**
     * API: Update the vault for the authenticated user
     */
    public function updateVault(Request $request)
    {
        $request->validate([
            'vault' => 'nullable',
            'salt' => 'nullable|string',
            'items' => 'nullable|array',
            'deleted_items' => 'nullable|array',
        ]);

        $vault = PasswordVault::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        if ($request->has('vault')) {
            $vault->encrypted_data = $request->vault;
        }

        if ($request->has('salt')) {
            $vault->salt = $request->salt;
        }

        $vault->save();

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                if (isset($itemData['id']) && isset($itemData['data'])) {
                    \Modules\PasswordSync\app\Models\PasswordItem::updateOrCreate(
                        ['password_vault_id' => $vault->id, 'remote_id' => $itemData['id']],
                        ['encrypted_data' => is_string($itemData['data']) ? $itemData['data'] : json_encode($itemData['data'])]
                    );
                }
            }
        }

        if ($request->has('deleted_items') && is_array($request->deleted_items)) {
            \Modules\PasswordSync\app\Models\PasswordItem::where('password_vault_id', $vault->id)
                ->whereIn('remote_id', $request->deleted_items)
                ->delete();
        }

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
