<?php

namespace App\Jobs;

use App\Models\NotificationCampaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendCampaignBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public NotificationCampaign $campaign;
    public array $validated;

    /**
     * Create a new job instance.
     */
    public function __construct(NotificationCampaign $campaign, array $validated)
    {
        $this->campaign = $campaign;
        $this->validated = $validated;
    }

    /**
     * Execute the job.
     */
    public function handle(Messaging $messaging): void
    {
        try {
            $notification = Notification::create($this->validated['title'], $this->validated['body']);

            if ($this->validated['audience_type'] === 'global') {
                $notification = $notification->withImageUrl(route('track.campaign.view', ['id' => $this->campaign->id]));

                $message = CloudMessage::new()
                    ->withTopic('global')
                    ->withNotification($notification);

                $trackingUrl = route('track.campaign', ['id' => $this->campaign->id]);
                if (! empty($this->validated['url'])) {
                    $trackingUrl .= '?redirect='.urlencode($this->validated['url']);
                }

                $message = $this->configureMessageData($message, $trackingUrl);
                $messaging->send($message);
            } else {
                // Personal targeting
                $query = User::query();

                if ($this->validated['personal_target'] === 'roles') {
                    $query->role($this->validated['roles']);
                } elseif ($this->validated['personal_target'] === 'specific') {
                    $query->whereIn('id', $this->validated['user_ids']);
                }

                // Chunking to handle large datasets
                $query->chunk(500, function ($users) use ($messaging, $notification) {
                    $messages = [];

                    foreach ($users as $user) {
                        if ($user->fcm_token) {
                            $viewUrl = route('track.campaign.view', ['id' => $this->campaign->id, 'user_id' => $user->id]);
                            $personalNotification = $notification->withImageUrl($viewUrl);

                            $trackingUrl = route('track.campaign', ['id' => $this->campaign->id, 'user_id' => $user->id]);
                            if (! empty($this->validated['url'])) {
                                $trackingUrl .= '&redirect='.urlencode($this->validated['url']);
                            }

                            $message = CloudMessage::new()
                                ->withTarget('token', $user->fcm_token)
                                ->withNotification($personalNotification);

                            $messages[] = $this->configureMessageData($message, $trackingUrl);
                        }
                    }

                    if (! empty($messages)) {
                        $messaging->sendAll($messages);
                    }
                });
            }

            $this->campaign->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            $this->campaign->update(['status' => 'failed']);
            \Log::error('Broadcast Notification Job Failed: '.$e->getMessage());
            throw $e;
        }
    }

    private function configureMessageData(CloudMessage $message, string $trackingUrl): CloudMessage
    {
        return $message->withData([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'url' => $trackingUrl,
        ])->withWebPushConfig([
            'fcm_options' => [
                'link' => $trackingUrl,
            ],
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
                        'sound' => 'default',
                    ],
                ],
            ]);
    }
}
