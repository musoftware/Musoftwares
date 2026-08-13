<?php

namespace App\Listeners;

use App\Models\OutgoingEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogOutgoingMailListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof MessageSending) {
            $this->handleSending($event);
        } elseif ($event instanceof MessageSent) {
            $this->handleSent($event);
        }
    }

    /**
     * Handle the MessageSending event (pre-log as failed, set header tracker).
     */
    protected function handleSending(MessageSending $event): void
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
                $notification = $event->data['__laravel_notification'];
                $mailClass = is_object($notification) ? get_class($notification) : $notification;
            } elseif (isset($event->data['__laravel_mailable'])) {
                $mailable = $event->data['__laravel_mailable'];
                $mailClass = is_object($mailable) ? get_class($mailable) : $mailable;
            }

            $outgoingEmail = OutgoingEmail::create([
                'to_email' => substr($toEmail, 0, 250),
                'subject' => $subject ? substr($subject, 0, 250) : null,
                'mail_class' => class_basename($mailClass),
                'status' => 'failed', // Initial state, will be updated to 'sent' upon MessageSent
                'sent_at' => now(), // Keep track of when sending was attempted
            ]);

            if (method_exists($message, 'getHeaders')) {
                $message->getHeaders()->addTextHeader('X-Outgoing-Email-ID', (string) $outgoingEmail->id);
            }
        } catch (Throwable $e) {
            Log::error('Failed to pre-log outgoing email: ' . $e->getMessage());
        }
    }

    /**
     * Handle the MessageSent event (update status to sent, remove header).
     */
    protected function handleSent(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $recordId = null;
            if (method_exists($message, 'getHeaders')) {
                $header = $message->getHeaders()->get('X-Outgoing-Email-ID');
                $recordId = $header ? $header->getBodyAsString() : null;
            }

            if ($recordId) {
                $outgoingEmail = OutgoingEmail::find($recordId);
                if ($outgoingEmail) {
                    $outgoingEmail->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
                
                if (method_exists($message, 'getHeaders')) {
                    $message->getHeaders()->remove('X-Outgoing-Email-ID');
                }
            } else {
                // Fallback: if header wasn't found or handleSending was skipped
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
                    $notification = $event->data['__laravel_notification'];
                    $mailClass = is_object($notification) ? get_class($notification) : $notification;
                } elseif (isset($event->data['__laravel_mailable'])) {
                    $mailable = $event->data['__laravel_mailable'];
                    $mailClass = is_object($mailable) ? get_class($mailable) : $mailable;
                }

                OutgoingEmail::create([
                    'to_email' => substr($toEmail, 0, 250),
                    'subject' => $subject ? substr($subject, 0, 250) : null,
                    'mail_class' => class_basename($mailClass),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to update sent log for outgoing email: ' . $e->getMessage());
        }
    }
}
