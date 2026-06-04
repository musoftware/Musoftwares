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

        // Update the user's fcm_token
        $user->fcm_token = $token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Token saved successfully'
        ]);
    }
}
