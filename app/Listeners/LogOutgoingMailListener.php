<?php

namespace App\Listeners;

use App\Models\OutgoingEmail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogOutgoingMailListener
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $recipients = [];
            if (method_exists($message, 'getTo')) {
                foreach ($message->getTo() as $address) {
                    if (is_object($address) && method_exists($address, 'getAddress')) {
                        $recipients[] = $address->getAddress();
                    } elseif (is_string($address)) {
                        $recipients[] = $address;
                    }
                }
            }

            $toEmail = !empty($recipients) ? implode(', ', $recipients) : 'N/A';
            $subject = method_exists($message, 'getSubject') ? $message->getSubject() : null;

            $mailClass = 'Mail';
            if (isset($event->data['__laravel_notification'])) {
                $mailClass = get_class($event->data['__laravel_notification']);
            } elseif (isset($event->data['__laravel_mailable'])) {
                $mailClass = get_class($event->data['__laravel_mailable']);
            }

            OutgoingEmail::create([
                'to_email' => substr($toEmail, 0, 250),
                'subject' => $subject ? substr($subject, 0, 250) : null,
                'mail_class' => class_basename($mailClass),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log outgoing email: ' . $e->getMessage());
        }
    }
}
