<?php

namespace App\Helpers;

use App\Jobs\SendFcmNotificationJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;

/**
 * Queues the push for SendFcmNotificationJob, which talks to Firebase. The channel itself never connects, so
 * creating it needs no Firebase credentials.
 */
class MuFcmChannel
{
    const TOKENS_PER_REQUEST = 500;

    protected Dispatcher $events;

    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    public function send(mixed $notifiable, Notification $notification): ?Collection
    {
        $tokens = Arr::wrap($notifiable->routeNotificationFor('fcm', $notification));

        if (empty($tokens)) {
            return null;
        }

        $fcmMessage = $notification->toFcm($notifiable);

        SendFcmNotificationJob::dispatch($tokens, $fcmMessage->jsonSerialize());

        return null;
    }

    protected function checkReportForFailures(mixed $notifiable, Notification $notification, MulticastSendReport $report): MulticastSendReport
    {
        Collection::make($report->getItems())
            ->filter(fn (SendReport $r) => $r->isFailure())
            ->each(fn (SendReport $r) => $this->dispatchFailedNotification($notifiable, $notification, $r));

        return $report;
    }

    protected function dispatchFailedNotification(mixed $notifiable, Notification $notification, SendReport $report): void
    {
        $this->events->dispatch(new NotificationFailed($notifiable, $notification, self::class, [
            'report' => $report,
        ]));
    }
}
