<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\NotificationCampaign;
use App\Models\User;
use Spatie\Permission\Models\Role;

class BroadcastNotificationController extends Controller
{
    /**
     * Show the form to send a broadcast notification.
     */
    public function index()
    {
        $campaigns = NotificationCampaign::latest()->get();
        $roles = Role::all(['id', 'name']);
        
        return Inertia::render('Admin/Notifications/Broadcast', [
            'campaigns' => $campaigns,
            'roles' => $roles,
        ]);
    }

    /**
     * Search users for async combobox.
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('q');
        
        $users = User::when($search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->limit(20)
            ->get(['id', 'name', 'email']);
            
        return response()->json($users);
    }

    /**
     * Send a global push notification or personalized to users.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'url' => 'nullable|url',
            'audience_type' => 'required|in:global,personal',
            'personal_target' => 'required_if:audience_type,personal|in:all,roles,specific',
            'roles' => 'required_if:personal_target,roles|array',
            'user_ids' => 'required_if:personal_target,specific|array',
        ]);

        try {
            $campaign = NotificationCampaign::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target_url' => $validated['url'] ?? null,
                'status' => 'sending',
                'audience_type' => $validated['audience_type'],
            ]);

            $messaging = app('firebase.messaging');
            $notification = Notification::create($validated['title'], $validated['body']);

            if ($validated['audience_type'] === 'global') {
                $notification = $notification->withImageUrl(route('track.campaign.view', ['id' => $campaign->id]));
                
                $message = CloudMessage::new()
                    ->withTopic('global')
                    ->withNotification($notification);
                    
                $trackingUrl = route('track.campaign', ['id' => $campaign->id]);
                if (!empty($validated['url'])) {
                    $trackingUrl .= '?redirect=' . urlencode($validated['url']);
                }

                $message = $this->configureMessageData($message, $trackingUrl);
                $messaging->send($message);
            } else {
                // Personal targeting
                $query = User::query();
                
                if ($validated['personal_target'] === 'roles') {
                    $query->role($validated['roles']);
                } elseif ($validated['personal_target'] === 'specific') {
                    $query->whereIn('id', $validated['user_ids']);
                }

                // Chunking to handle large datasets
                $query->chunk(500, function ($users) use ($messaging, $notification, $campaign, $validated) {
                    $messages = [];

                    foreach ($users as $user) {
                        if ($user->fcm_token) {
                            $viewUrl = route('track.campaign.view', ['id' => $campaign->id, 'user_id' => $user->id]);
                            $personalNotification = $notification->withImageUrl($viewUrl);

                            $trackingUrl = route('track.campaign', ['id' => $campaign->id, 'user_id' => $user->id]);
                            if (!empty($validated['url'])) {
                                $trackingUrl .= '&redirect=' . urlencode($validated['url']);
                            }

                            $message = CloudMessage::new()
                                ->withTarget('token', $user->fcm_token)
                                ->withNotification($personalNotification);

                            $messages[] = $this->configureMessageData($message, $trackingUrl);
                        }
                    }

                    if (!empty($messages)) {
                        $messaging->sendAll($messages);
                    }
                });
            }

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

    private function configureMessageData($message, $trackingUrl)
    {
        return $message->withData([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'url' => $trackingUrl
        ])->withWebPushConfig([
            'fcm_options' => [
                'link' => $trackingUrl
            ]
        ])->withHighestPossiblePriority()
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
    }

    /**
     * Show the statistics and details of a specific broadcast campaign.
     */
    public function show($id)
    {
        $campaign = NotificationCampaign::with([
            'views.user'
        ])->findOrFail($id);
        
        return Inertia::render('Admin/Notifications/Show', [
            'campaign' => $campaign
        ]);
    }
}
