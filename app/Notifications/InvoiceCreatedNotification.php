<?php

namespace App\Notifications;

use App\Models\AdminSettings;
use App\Notifications\Traits\BuildsFcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use BuildsFcmMessage, Queueable;

    public $invoice;

    public ?array $forceChannels = null;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        if (is_array($this->forceChannels) && ! empty($this->forceChannels)) {
            return $this->forceChannels;
        }

        return AdminSettings::invoiceNotificationChannels('invoice_created');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $invoiceNumber = $invoice->invoice_number ?? '#'.($invoice->id ?? '');
        $dueDate = $invoice->due_date 
            ? \Carbon\Carbon::parse($invoice->due_date)->setTimezone('Africa/Cairo')->format('Y-m-d') 
            : \Carbon\Carbon::now('Africa/Cairo')->addDays(7)->format('Y-m-d');
        
        $totalAmountFormatted = method_exists($invoice, 'total_str') 
            ? $invoice->total_str() 
            : (number_format((float) ($invoice->total() ?? $invoice->amount ?? 0), 2) . ' ' . ($invoice->currency_id ?? 'EGP'));
            
        $invoiceUrl = url('/app/invoices/'.($invoice->id ?? ''));

        $user = $notifiable instanceof \App\Models\User ? $notifiable : ($invoice->user ?? null);
        $loyaltyService = app(\App\Services\LoyaltyService::class);
        $userSummary = $user ? $loyaltyService->getUserSummary($user) : null;

        $tierName = $userSummary['current_tier']->name ?? 'Bronze';
        $tierSlug = $userSummary['current_tier']->slug ?? 'bronze';
        $tierDiscount = (float) ($userSummary['current_tier']->invoice_discount_pct ?? 0);
        $pointsBalance = (int) ($userSummary['balance'] ?? 0);
        $rate = \App\Services\LoyaltyService::POINTS_TO_CURRENCY_RATE;
        $pointsCashValue = round($pointsBalance * $rate, 2);
        $currencySymbol = $invoice->currency_id ? ($invoice->currencyRelation?->symbol ?? $invoice->currency_id) : 'EGP';
        $pointsCashValueFormatted = number_format($pointsCashValue, 2) . ' ' . $currencySymbol;

        $rule = \App\Models\LoyaltyRule::forEvent('invoice_payment');
        $basePoints = $rule ? (int) $rule->base_points : 200;
        $potentialEarlyPoints = (int) round($basePoints * 2.0);

        return (new MailMessage)
            ->subject(__('general.notif_invoice_created_subject') . ' — ' . $invoiceNumber)
            ->view('emails.invoices.created_with_loyalty', [
                'clientName' => $notifiable->name ?? 'Valued Client',
                'invoiceNumber' => $invoiceNumber,
                'totalAmountFormatted' => $totalAmountFormatted,
                'dueDateFormatted' => $dueDate,
                'invoiceUrl' => $invoiceUrl,
                'tierName' => $tierName,
                'tierSlug' => $tierSlug,
                'tierDiscount' => $tierDiscount,
                'pointsBalance' => $pointsBalance,
                'pointsCashValueFormatted' => $pointsCashValueFormatted,
                'potentialEarlyPoints' => $potentialEarlyPoints,
            ]);
    }

    public function toFcm(object $notifiable)
    {
        return $this->fcmMessage(
            __('general.notif_invoice_created_title'),
            __('general.notif_invoice_created_body', ['invoice' => ($this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''))]),
            [
                'url' => '/app/invoices/'.($this->invoice->id ?? ''),
                'type' => 'invoice_created',
                'id' => (string) ($this->invoice->id ?? ''),
            ]
        );
    }

    /**
     * SMS payload — kept short to fit per-gateway segment limits.
     */
    public function toSms(object $notifiable): ?string
    {
        $amount = $this->invoice->total() ?? $this->invoice->unpaid ?? '';
        $currency = $this->invoice->currency_id ?? '';

        return sprintf(
            'New invoice %s for %s %s. View: %s',
            $this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''),
            $amount,
            $currency,
            url('/app/invoices/'.($this->invoice->id ?? ''))
        );
    }

    /**
     * WhatsApp payload — mirrors the mail body but in plain text.
     */
    public function toWhatsapp(object $notifiable): ?string
    {
        return sprintf(
            "%s\nInvoice %s has been created.\nView: %s",
            __('general.hello_name', ['name' => $notifiable->name ?? '']),
            $this->invoice->invoice_number ?? '#'.($this->invoice->id ?? ''),
            url('/app/invoices/'.($this->invoice->id ?? ''))
        );
    }
}
