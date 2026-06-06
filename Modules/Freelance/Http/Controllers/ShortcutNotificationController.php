<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ShortcutNotificationController extends Controller
{
    /**
     * Generate a new Sanctum API token for the iOS Shortcut.
     */
    public function generateToken(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Revoke any existing iOS Shortcut tokens
        $user->tokens()->where('name', 'ios-shortcut')->delete();
        
        // Create new token
        $token = $user->createToken('ios-shortcut')->plainTextToken;
        
        return redirect()->back()->with('success', __('freelance.ios_shortcut_token_generated'))->with('ios_shortcut_token', $token);
    }

    /**
     * Fetch unread notifications for the iOS Shortcut.
     */
    public function fetch(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $lastSync = $user->last_shortcut_sync_at;
        
        // Fetch unread notifications newer than last sync
        $query = $user->unreadNotifications();
        
        if ($lastSync) {
            $query->where('created_at', '>', $lastSync);
        }
        
        $notifications = $query->get();
        
        // Update last sync time
        $user->last_shortcut_sync_at = now();
        $user->save();
        
        // Format for iOS Shortcut
        $formatted = $notifications->map(function ($notification) {
            // Determine URL from notification data. 
            // Fallback to freelance dashboard if not specifically set.
            $url = $notification->data['url'] ?? route('freelance.dashboard');
            
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Freelancer Notification',
                'body' => $notification->data['message'] ?? $notification->data['body'] ?? 'You have a new notification.',
                'url' => $url,
            ];
        });
        
        return response()->json([
            'success' => true,
            'notifications' => $formatted,
        ]);
    }
}
