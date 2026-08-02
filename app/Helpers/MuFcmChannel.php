<?php

namespace App\Helpers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;

class MuFcmChannel
{
    const TOKENS_PER_REQUEST = 500;

    protected Dispatcher $events;

    protected Messaging $messaging;

    public function __construct(Dispatcher $events, Messaging $messaging)
    {
        $this->events = $events;
        $this->messaging = $messaging;
    }

    public function send(mixed $notifiable, Notification $notification): ?Collection
    {
        $tokens = Arr::wrap($notifiable->routeNotificationFor('fcm', $notification));

        if (empty($tokens)) {
            return null;
        }

        $fcmMessage = $notification->toFcm($notifiable);

        \App\Jobs\SendFcmNotificationJob::dispatch($tokens, $fcmMessage->jsonSerialize());

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
