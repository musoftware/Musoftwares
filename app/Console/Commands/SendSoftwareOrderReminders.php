<?php

namespace App\Console\Commands;

use App\Mail\SoftwareOrderReminderMail;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Modules\SoftwareStore\Models\SoftwareOrder;

class SendSoftwareOrderReminders extends Command
{
    private const MIN_AGE_HOURS = 2;

    private const MAX_AGE_HOURS = 48;

    private const LINK_VALID_HOURS = 72;

    protected $signature = 'software-orders:send-reminders';

    protected $description = 'Email once the customer of a store order that was started 2 to 48 hours ago and is still unpaid.';

    public function handle(): int
    {
        if (! Schema::hasColumn('software_orders', 'reminder_sent_at')) {
            $this->warn('Run the software store migrations first (the reminder_sent_at column is missing).');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($this->dueOrders() as $order) {
            $sent += $this->remind($order) ? 1 : 0;
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    /** @return Collection<int, SoftwareOrder> */
    private function dueOrders()
    {
        return SoftwareOrder::with('product')
            ->where('status', SoftwareOrder::STATUS_PENDING)
            ->whereNull('reminder_sent_at')
            ->whereBetween('created_at', [now()->subHours(self::MAX_AGE_HOURS), now()->subHours(self::MIN_AGE_HOURS)])
            ->get();
    }

    private function remind(SoftwareOrder $order): bool
    {
        if (! $order->product?->is_active || ! filter_var($order->client_email, FILTER_VALIDATE_EMAIL) || $this->paidLater($order)) {
            return false;
        }

        // The discount in the mail is counted from this moment, so it is set before the mail is built.
        $order->forceFill(['reminder_sent_at' => now()])->save();

        try {
            Mail::to($order->client_email)->send(new SoftwareOrderReminderMail($order, $this->resumeUrl($order)));
        } catch (\Throwable $e) {
            $order->forceFill(['reminder_sent_at' => null])->save();
            Log::warning('Software order reminder failed.', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    /** The customer tried again and paid, so a reminder for the first try would only confuse. */
    private function paidLater(SoftwareOrder $order): bool
    {
        return SoftwareOrder::where('software_product_id', $order->software_product_id)
            ->where('client_email', $order->client_email)
            ->where('status', SoftwareOrder::STATUS_PAID)
            ->where('created_at', '>=', $order->created_at)
            ->exists();
    }

    private function resumeUrl(SoftwareOrder $order): string
    {
        return URL::temporarySignedRoute('software.orders.resume', now()->addHours(self::LINK_VALID_HOURS), ['uuid' => $order->uuid]);
    }
}
