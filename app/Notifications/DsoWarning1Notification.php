<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class DsoWarning1Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public float $totalUnpaidAmount;

    public function __construct(float $totalUnpaidAmount)
    {
        $this->totalUnpaidAmount = $totalUnpaidAmount;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->lang ?? 'ar';
        App::setLocale($locale);

        $formattedAmount = FinanceHelper::instance()->format_money($this->totalUnpaidAmount, $notifiable->currency_id);

        return (new MailMessage)
            ->subject(trans('dso.warn_1_subject'))
            ->greeting(trans('dso.warn_1_greeting'))
            ->line(trans('dso.warn_1_body'))
            ->line(trans('dso.warn_1_irreversible'))
            ->line(trans('dso.warn_1_total', ['amount' => $formattedAmount]))
            ->line(trans('dso.warn_1_footer'));
    }

    public function toArray(object $notifiable): array
    {
        $locale = $notifiable->lang ?? 'ar';
        App::setLocale($locale);
        $formattedAmount = FinanceHelper::instance()->format_money($this->totalUnpaidAmount, $notifiable->currency_id);

        return [
            'type' => 'dso_warning_1',
            'total_unpaid' => $this->totalUnpaidAmount,
            'message' => trans('dso.warn_1_subject') . ' - ' . trans('dso.warn_1_total', ['amount' => $formattedAmount]),
        ];
    }
}
