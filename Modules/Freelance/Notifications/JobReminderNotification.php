<?php

namespace Modules\Freelance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Freelance\Models\Job;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class JobReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $job;

    /**
     * Create a new notification instance.
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Define the FCM push notification payload.
     */
    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
                title: __('freelance.job_reminder_subject'),
                body: __('freelance.job_reminder_body', ['title' => $this->job->title]),
                image: 'https://musoftwares.com/icon.png'
            )))
            ->data([
                'job_id' => (string) $this->job->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'url' => route('freelance.jobs.show', $this->job->id)
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#000000',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject(__('freelance.job_reminder_subject'))
                    ->line(__('freelance.job_reminder_body', ['title' => $this->job->title]))
                    ->action('View Job', route('freelance.jobs.show', $this->job->id))
                    ->line(__('general.submit_your_proposal_to_win_this_job'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'title' => $this->job->title,
            'budget' => $this->job->budget,
            'currency_id' => $this->job->currency_id,
            'message' => __('freelance.job_reminder_body', ['title' => $this->job->title]),
        ];
    }
}
