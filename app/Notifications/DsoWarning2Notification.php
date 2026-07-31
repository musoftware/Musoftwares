<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class DsoWarning2Notification extends Notification implements ShouldQueue
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
            ->subject(trans('dso.warn_2_subject'))
            ->greeting(trans('dso.warn_2_greeting'))
            ->line(trans('dso.warn_2_body'))
            ->line(trans('dso.warn_2_irreversible'))
            ->line(trans('dso.warn_2_total', ['amount' => $formattedAmount]))
            ->line(trans('dso.warn_2_footer'));
    }

    public function toArray(object $notifiable): array
    {
        $locale = $notifiable->lang ?? 'ar';
        App::setLocale($locale);
        $formattedAmount = FinanceHelper::instance()->format_money($this->totalUnpaidAmount, $notifiable->currency_id);

        return [
            'type' => 'dso_warning_2',
            'total_unpaid' => $this->totalUnpaidAmount,
            'message' => trans('dso.warn_2_subject') . ' - ' . trans('dso.warn_2_total', ['amount' => $formattedAmount]),
        ];
    }
}
