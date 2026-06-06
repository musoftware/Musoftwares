<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\NotificationCampaign;

class BroadcastNotificationController extends Controller
{
    /**
     * Show the form to send a broadcast notification.
     */
    public function index()
    {
        $campaigns = NotificationCampaign::latest()->get();
        return Inertia::render('Admin/Notifications/Broadcast', [
            'campaigns' => $campaigns
        ]);
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
            $campaign = NotificationCampaign::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target_url' => $validated['url'] ?? null,
                'status' => 'sending',
            ]);

            $messaging = app('firebase.messaging');
            
            $notification = Notification::create($validated['title'], $validated['body'])
                ->withImageUrl(route('track.campaign.view', ['id' => $campaign->id]));
            
            $message = CloudMessage::new()
                ->withTopic('global')
                ->withNotification($notification);
                
            $trackingUrl = route('track.campaign', ['id' => $campaign->id]);
            if (!empty($validated['url'])) {
                $trackingUrl .= '?redirect=' . urlencode($validated['url']);
            }

            $message = $message->withData([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'url' => $trackingUrl
            ])->withWebPushConfig([
                'fcm_options' => [
                    'link' => $trackingUrl
                ]
            ]);

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

            $campaign->update(['status' => 'completed']);

            return back()->with('success', __('admin.notification_sent_successfully'));
            
        } catch (\Exception $e) {
            if (isset($campaign)) {
                $campaign->update(['status' => 'failed']);
            }
            \Log::error('Broadcast Notification Failed: ' . $e->getMessage());
            return back()->with('error', __('admin.notification_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Show the statistics and details of a specific broadcast campaign.
     */
    public function show($id)
    {
        $campaign = NotificationCampaign::findOrFail($id);
        
        return Inertia::render('Admin/Notifications/Show', [
            'campaign' => $campaign
        ]);
    }
}
