<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class BroadcastNotificationController extends Controller
{
    /**
     * Show the form to send a broadcast notification.
     */
    public function index()
    {
        return Inertia::render('Admin/Notifications/Broadcast');
    }

    /**
     * Send a global push notification to all users subscribed to the 'global' topic.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'url' => 'nullable|url',
        ]);

        try {
            $messaging = app('firebase.messaging');
            
            $notification = Notification::create($validated['title'], $validated['body']);
            
            $message = CloudMessage::new()
                ->withTopic('global')
                ->withNotification($notification);
                
            if (!empty($validated['url'])) {
                $message = $message->withData([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'url' => $validated['url']
                ])->withWebPushConfig([
                    'fcm_options' => [
                        'link' => $validated['url']
                    ]
                ]);
            }

            // Customize for platforms
            $message = $message->withHighestPossiblePriority()
                ->withAndroidConfig([
                    'notification' => [
                        'color' => '#000000',
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ])
                ->withApnsConfig([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ],
                    ],
                ]);

            $messaging->send($message);

            return back()->with('success', __('admin.notification_sent_successfully'));
            
        } catch (\Exception $e) {
            \Log::error('Broadcast Notification Failed: ' . $e->getMessage());
            return back()->with('error', __('admin.notification_failed') . ': ' . $e->getMessage());
        }
    }
}
