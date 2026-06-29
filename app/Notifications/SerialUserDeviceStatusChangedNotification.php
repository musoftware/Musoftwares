<?php

namespace App\Notifications;

use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SerialUserDeviceStatusChangedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $device;

    public $oldStatus;

    public $newStatus;

    public function __construct($device, $oldStatus, $newStatus)
    {
        $this->device = $device;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('general.notif_device_status_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_device_status_body', [
                'device' => ($this->device->device_id ?? ''),
                'status' => $this->newStatus,
            ]));
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_device_status_title'),
            __('general.notif_device_status_body', [
                'device' => ($this->device->device_id ?? ''),
                'status' => $this->newStatus,
            ]),
            [
                'url' => '/app/devices',
                'type' => 'device_status_changed',
                'id' => (string) ($this->device->id ?? ''),
                'old_status' => (string) $this->oldStatus,
                'new_status' => (string) $this->newStatus,
            ]
        );
    }
}
