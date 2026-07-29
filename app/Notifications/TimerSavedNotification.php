<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Invoice;
use App\Models\InvoiceItemTimer;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimerSavedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $target;

    /**
     * Create a new notification instance.
     */
    public function __construct($target)
    {
        $this->target = $target;
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
        $discountInfo = $this->getDiscountInfo();
        $amount = $discountInfo['billed_amount_str'];
        $invoiceId = $this->getInvoiceId();

        $mail = (new MailMessage)
            ->subject(__('general.notif_timer_saved_subject'))
            ->greeting(__('general.hello_name', ['name' => $notifiable->name ?? '']))
            ->line(__('general.notif_timer_saved_body', [
                'title' => $title,
                'time' => $timeConsumed,
                'amount' => $amount,
            ]));

        if ($discountInfo['has_discount']) {
            $mail->line(__('general.full_real_value').': '.$discountInfo['full_real_value_str']);
            $mail->line(__('general.discount_savings').': -'.$discountInfo['discount_savings_str']);
        }

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
        $discountInfo = $this->getDiscountInfo();

        return [
            'timer_id' => $this->target instanceof InvoiceItemTimer ? $this->target->id : null,
            'invoice_id' => $this->getInvoiceId(),
            'title' => $this->getItemTitle(),
            'time_consumed' => $this->getFormattedTimeConsumed(),
            'amount' => $discountInfo['billed_amount_str'],
            'raw_amount' => $discountInfo['billed_amount'],
            'full_real_value' => $discountInfo['full_real_value'],
            'full_real_value_str' => $discountInfo['full_real_value_str'],
            'discount_savings' => $discountInfo['discount_savings'],
            'discount_savings_str' => $discountInfo['discount_savings_str'],
            'has_discount' => $discountInfo['has_discount'],
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
        $discountInfo = $this->getDiscountInfo();
        $amount = $discountInfo['billed_amount_str'];
        $invoiceId = $this->getInvoiceId();

        $bodyText = __('general.notif_timer_saved_body', [
            'title' => $title,
            'time' => $timeConsumed,
            'amount' => $amount,
        ]);

        if ($discountInfo['has_discount']) {
            $bodyText .= ' ('.__('general.discount_savings').': -'.$discountInfo['discount_savings_str'].')';
        }

        return $this->fcmMessage(
            __('general.notif_timer_saved_title'),
            $bodyText,
            [
                'url' => $invoiceId ? '/app/invoices/'.$invoiceId : '/app/invoices',
                'type' => 'timer_saved',
                'invoice_id' => (string) $invoiceId,
                'time_consumed' => (string) $timeConsumed,
                'amount' => (string) $amount,
                'discount_savings' => (string) $discountInfo['discount_savings_str'],
                'raw_amount' => (string) $discountInfo['billed_amount'],
                'currency_id' => (string) $this->getCurrencyId(),
            ]
        );
    }

    /**
     * Get invoice ID.
     */
    protected function getInvoiceId(): ?int
    {
        if ($this->target instanceof Invoice) {
            return $this->target->id;
        }
        if ($this->target instanceof InvoiceItemTimer) {
            return $this->target->invoiceItem?->invoice_id;
        }
        return $this->target->invoice_id ?? $this->target->invoiceItem?->invoice_id ?? null;
    }

    /**
     * Get title of item or timer reason.
     */
    protected function getItemTitle(): string
    {
        if ($this->target instanceof Invoice) {
            return __('general.time_tracking').' (#'.$this->target->enc_id().')';
        }
        if ($this->target instanceof InvoiceItemTimer) {
            return $this->target->invoiceItem?->item_title 
                ?? $this->target->reason 
                ?? __('general.time_tracking');
        }
        return $this->target->item_title ?? $this->target->title ?? __('general.time_tracking');
    }

    /**
     * Get currency ID associated with timer or invoice.
     */
    protected function getCurrencyId(): int
    {
        if ($this->target instanceof Invoice) {
            return (int) ($this->target->currency_id ?? 2);
        }
        return (int) ($this->target->currency_id 
            ?? $this->target->invoiceItem?->invoice?->currency_id 
            ?? 2);
    }

    /**
     * Format time consumed in hours and minutes.
     */
    protected function getFormattedTimeConsumed(): string
    {
        $seconds = 0;
        if ($this->target instanceof Invoice) {
            $seconds = (int) $this->target->total_timer();
        } elseif (is_object($this->target) && method_exists($this->target, 'diff')) {
            $seconds = (int) $this->target->diff();
        }

        if ($seconds <= 0 && ! empty($this->target->date_start) && ! empty($this->target->date_end)) {
            $seconds = abs(strtotime($this->target->date_end) - strtotime($this->target->date_start));
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

    /**
     * Get discount and full real value breakdown.
     */
    protected function getDiscountInfo(): array
    {
        $seconds = 0;
        $billedAmount = 0.0;
        $currencyId = $this->getCurrencyId();

        if ($this->target instanceof Invoice) {
            $seconds = (int) $this->target->total_timer();
            $invoice = $this->target;
            if ($invoice->relationLoaded('items')) {
                foreach ($invoice->items as $item) {
                    $timers = $item->relationLoaded('timers') ? $item->timers : $item->timers()->get();
                    foreach ($timers as $timer) {
                        $billedAmount += (float) ($timer->amount ?? 0);
                    }
                }
            }
        } else {
            if (is_object($this->target) && method_exists($this->target, 'diff')) {
                $seconds = (int) $this->target->diff();
            }
            if ($seconds <= 0 && ! empty($this->target->date_start) && ! empty($this->target->date_end)) {
                $seconds = abs(strtotime($this->target->date_end) - strtotime($this->target->date_start));
            }
            $billedAmount = (float) ($this->target->amount ?? 0);
            $invoice = $this->target->invoiceItem?->invoice;
        }

        $user = $invoice?->user ?? ($this->target->user ?? null);

        $baseRate = FinanceHelper::calculateOverheadHourlyRate();
        $systemBaseRate = CurrenciesExchange::RateToday(
            $baseRate,
            AdminSettings::GetValue('business_currency', 2),
            $currencyId
        );

        $clientRate = 0;
        $isCustomRateEnabled = false;
        if ($user) {
            $isCustomRateEnabled = (bool) ($user->enable_custom_hour_rate ?? false);
            if ((float) ($user->hour_rate ?? 0) > 0) {
                $clientRate = CurrenciesExchange::RateToday(
                    $user->hour_rate,
                    $user->hour_rate_currency_id ?? $user->hour_rate_currency ?? $user->currency_id ?? 1,
                    $currencyId
                );
            }
        }

        $effectiveRate = ($isCustomRateEnabled && $clientRate > 0) ? $clientRate : $systemBaseRate;
        $fullRealValue = ($seconds / 3600) * $effectiveRate;
        $discountSavings = max(0, $fullRealValue - $billedAmount);
        $hasDiscount = $discountSavings > 0.01;

        return [
            'full_real_value' => $fullRealValue,
            'full_real_value_str' => FinanceHelper::instance()->format_money($fullRealValue, $currencyId),
            'billed_amount' => $billedAmount,
            'billed_amount_str' => FinanceHelper::instance()->format_money($billedAmount, $currencyId),
            'discount_savings' => $discountSavings,
            'discount_savings_str' => FinanceHelper::instance()->format_money($discountSavings, $currencyId),
            'has_discount' => $hasDiscount,
        ];
    }
}
