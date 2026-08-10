<?php

namespace App\Mail;

use App\Helpers\FinanceHelper;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecurringInvoicePaymentRequiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        $invoiceNum = $this->invoice->invoice_number ?? '#'.$this->invoice->id;

        return new Envelope(
            subject: "Payment Required: Invoice {$invoiceNum}",
        );
    }

    public function content(): Content
    {
        $invoiceCurrency = $this->invoice->currency_id ?? $this->user->currency_id;

        $invoiceAmountNum = (float) ($this->invoice->unpaid > 0 ? $this->invoice->unpaid : $this->invoice->total());
        $userBalanceNum = (float) ($this->user->user_balance ?? 0);
        $shortfallNum = max(0, $invoiceAmountNum - $userBalanceNum);

        $finance = FinanceHelper::instance();
        $formattedAmount = $finance->format_money($invoiceAmountNum, $invoiceCurrency);
        $formattedBalance = $finance->format_money($userBalanceNum, $this->user->currency_id);
        $formattedShortfall = $finance->format_money($shortfallNum, $invoiceCurrency);

        $invoiceTitle = 'Recurring Invoice';
        if ($this->invoice->items && $this->invoice->items->first()) {
            $invoiceTitle = $this->invoice->items->first()->item_title;
        }

        return new Content(
            view: 'emails.recurring_invoice_payment_required',
            with: [
                'userName' => $this->user->name ?? 'Customer',
                'invoiceNumber' => $this->invoice->invoice_number ?? (string) $this->invoice->id,
                'invoiceTitle' => $invoiceTitle,
                'invoiceAmount' => $formattedAmount,
                'userBalance' => $formattedBalance,
                'shortfall' => $formattedShortfall,
                'invoiceUrl' => url('/app/invoices/'.$this->invoice->id),
            ]
        );
    }
}
