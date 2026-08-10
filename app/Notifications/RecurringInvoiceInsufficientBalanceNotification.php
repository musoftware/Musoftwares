<?php

namespace App\Notifications;

use App\Helpers\FinanceHelper;
use App\Mail\RecurringInvoicePaymentRequiredMail;
use App\Models\AdminSettings;
use App\Models\Invoice;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RecurringInvoiceInsufficientBalanceNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): RecurringInvoicePaymentRequiredMail
    {
        return new RecurringInvoicePaymentRequiredMail($this->invoice, $notifiable);
    }

    public function toFcm(object $notifiable)
    {
        $invoiceNum = $this->invoice->invoice_number ?? '#'.$this->invoice->id;
        $amount = (float) ($this->invoice->unpaid > 0 ? $this->invoice->unpaid : $this->invoice->total());
        $currencyId = $this->invoice->currency_id ?? $notifiable->currency_id;
        $formattedAmount = FinanceHelper::instance()->format_money($amount, $currencyId);

        return $this->fcmMessage(
            "Payment Required: Invoice {$invoiceNum}",
            "Your wallet balance is insufficient to auto-pay invoice {$invoiceNum} ({$formattedAmount}). Please tap to pay online.",
            [
                'url' => '/app/invoices/'.$this->invoice->id,
                'type' => 'recurring_invoice_payment_required',
                'invoice_id' => (string) $this->invoice->id,
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        $invoiceNum = $this->invoice->invoice_number ?? '#'.$this->invoice->id;

        return [
            'invoice_id' => $this->invoice->id,
            'message' => "Automatic payment for invoice {$invoiceNum} failed due to insufficient wallet balance.",
            'url' => '/app/invoices/'.$this->invoice->id,
        ];
    }
}
