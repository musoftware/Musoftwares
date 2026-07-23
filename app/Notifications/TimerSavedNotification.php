<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use App\Models\InvoiceItemTimer;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimerSavedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $timer;

    /**
     * Create a new notification instance.
     */
    public function __construct($timer)
    {
        $this->timer = $timer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->getItemTitle();
        $timeConsumed = $this->getFormattedTimeConsumed();
        $amount = $this->getFormattedAmount();
        $invoiceId = $this->timer->invoiceItem?->invoice_id;

        $mail = (new MailMessage)
            ->subject(__('general.notif_timer_saved_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_timer_saved_body', [
                'title' => $title,
                'time' => $timeConsumed,
                'amount' => $amount,
            ]));

        if ($invoiceId) {
            $mail->action(__('general.view_invoice'), url('/app/invoices/'.$invoiceId));
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'timer_id' => $this->timer->id,
            'invoice_id' => $this->timer->invoiceItem?->invoice_id,
            'title' => $this->getItemTitle(),
            'time_consumed' => $this->getFormattedTimeConsumed(),
            'amount' => $this->getFormattedAmount(),
            'raw_amount' => $this->timer->amount ?? 0,
            'currency_id' => $this->getCurrencyId(),
        ];
    }

    /**
     * Get the FCM push representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        $title = $this->getItemTitle();
        $timeConsumed = $this->getFormattedTimeConsumed();
        $amount = $this->getFormattedAmount();
        $invoiceId = $this->timer->invoiceItem?->invoice_id;

        return $this->fcmMessage(
            __('general.notif_timer_saved_title'),
            __('general.notif_timer_saved_body', [
                'title' => $title,
                'time' => $timeConsumed,
                'amount' => $amount,
            ]),
            [
                'url' => $invoiceId ? '/app/invoices/'.$invoiceId : '/app/invoices',
                'type' => 'timer_saved',
                'timer_id' => (string) $this->timer->id,
                'time_consumed' => (string) $timeConsumed,
                'amount' => (string) $amount,
                'raw_amount' => (string) ($this->timer->amount ?? 0),
                'currency_id' => (string) $this->getCurrencyId(),
            ]
        );
    }

    /**
     * Get title of item or timer reason.
     */
    protected function getItemTitle(): string
    {
        return $this->timer->invoiceItem?->item_title 
            ?? $this->timer->reason 
            ?? __('general.time_tracking');
    }

    /**
     * Get currency ID associated with timer or invoice.
     */
    protected function getCurrencyId(): int
    {
        return (int) ($this->timer->currency_id 
            ?? $this->timer->invoiceItem?->invoice?->currency_id 
            ?? 2);
    }

    /**
     * Format currency amount for display.
     */
    protected function getFormattedAmount(): string
    {
        $amount = (float) ($this->timer->amount ?? 0);
        $currencyId = $this->getCurrencyId();

        return FinanceHelper::instance()->format_money($amount, $currencyId);
    }

    /**
     * Format time consumed in hours and minutes.
     */
    protected function getFormattedTimeConsumed(): string
    {
        $seconds = 0;
        if (is_object($this->timer) && method_exists($this->timer, 'diff')) {
            $seconds = (int) $this->timer->diff();
        }

        if ($seconds <= 0 && ! empty($this->timer->date_start) && ! empty($this->timer->date_end)) {
            $seconds = abs(strtotime($this->timer->date_end) - strtotime($this->timer->date_start));
        }

        if ($seconds <= 0) {
            return '0m';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        if ($minutes > 0) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        return "{$remainingSeconds}s";
    }
}
