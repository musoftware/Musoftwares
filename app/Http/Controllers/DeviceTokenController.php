<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Store a newly created FCM device token in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();
        $token = $request->input('token');

        // Ensure the token is saved in the device_tokens table
        $user->deviceTokens()->firstOrCreate([
            'token' => $token,
        ]);

        // Also update the fallback column for compatibility if needed
        $user->fcm_token = $token;
        $user->save();

        try {
            // Subscribe to the global topic for admin broadcasts
            $messaging = app('firebase.messaging');
            $messaging->subscribeToTopic('global', $token);
        } catch (\Exception $e) {
            \Log::error('Failed to subscribe token to global topic: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Token saved successfully',
        ]);
    }
}
