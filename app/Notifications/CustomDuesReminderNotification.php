<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomDuesReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $subject;
    public string $body;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject($this->subject);

        // Convert newlines in the body to separate mail lines
        $lines = explode("\n", $this->body);
        foreach ($lines as $line) {
            $mail->line($line);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'custom_dues_reminder',
            'subject' => $this->subject,
            'message' => 'Custom unpaid invoices reminder sent to client.',
        ];
    }
}
