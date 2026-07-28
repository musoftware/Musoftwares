<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DailyNewServicesDigestNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public Collection $websiteServices;
    public Collection $marketplaceServices;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $websiteServices, Collection $marketplaceServices)
    {
        $this->websiteServices = $websiteServices;
        $this->marketplaceServices = $marketplaceServices;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable->enable_notifications) {
            return ['mail', 'fcm'];
        }
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('services.daily_digest_subject'))
            ->view('emails.services.digest', [
                'websiteServices' => $this->websiteServices,
                'marketplaceServices' => $this->marketplaceServices,
                'user' => $notifiable,
            ]);
    }

    /**
     * Get the Firebase Cloud Message representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('services.daily_digest_fcm_title'),
            __('services.daily_digest_fcm_body'),
            [
                'url' => '/app/services',
                'type' => 'daily_services_digest',
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'website_services_count' => $this->websiteServices->count(),
            'marketplace_services_count' => $this->marketplaceServices->count(),
            'message' => 'Daily new services and updates digest.',
        ];
    }
}
