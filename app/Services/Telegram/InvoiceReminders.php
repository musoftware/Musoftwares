<?php

namespace App\Services\Telegram;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

/**
 * An unpaid invoice is mentioned in the customer's Telegram, with a pay button: once every few days, from a day after
 * it was made and for two months at most. Only accounts that linked Telegram and kept alerts on.
 */
class InvoiceReminders
{
    private const EVERY_DAYS = 3;

    private const FOR_DAYS = 60;

    private const PER_RUN = 200;

    public function __construct(private readonly CustomerAlerts $alerts) {}

    public function sendDue(): int
    {
        $due = Invoice::query()
            ->with('user')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNotNull('uuid')
            ->whereBetween('created_at', [now()->subDays(self::FOR_DAYS), now()->subDay()])
            ->whereHas('user', fn ($query) => $query->whereNotNull('telegram_chat_id')->where('telegram_alerts_off', false))
            ->limit(self::PER_RUN)
            ->get()
            // the file cache keeps the last reminder (production's default cache forgets between requests)
            ->filter(fn (Invoice $invoice) => Cache::store('file')->add('telegram_invoice_reminder_'.$invoice->id, true, now()->addDays(self::EVERY_DAYS)));

        $due->each(fn (Invoice $invoice) => $this->remind($invoice));

        return $due->count();
    }

    private function remind(Invoice $invoice): void
    {
        $locale = CustomerAlerts::localeOf($invoice->user);
        $previous = app()->getLocale();
        app()->setLocale($locale);
        try {
            $this->alerts->send($invoice->user, __('telegram.invoice_reminder', ['id' => $invoice->id, 'amount' => AccountScreens::amount($invoice)]), [AccountScreens::payLink($invoice)]);
        } finally {
            app()->setLocale($previous);
        }
    }
}
