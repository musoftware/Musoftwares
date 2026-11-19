<?php

declare(strict_types=1);

namespace App\Support;

use App\Helpers\TextHelper;
use App\Http\Resources\InvoiceResource;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * What a client may see of an invoice: the same shape for the signed-in pay page and the
 * guest link. Costs, margins and affiliate details stay out on purpose.
 */
final class InvoiceSummary
{
    /** @return array<string, mixed> */
    public static function of(Invoice $invoice): array
    {
        $invoice->loadMissing(['items.timers', 'project', 'user']);

        return [
            'id' => $invoice->id,
            'uuid' => $invoice->uuid,
            'number' => (string) $invoice->id,
            'status' => (string) $invoice->status,
            'issued_at' => $invoice->created_at?->format('Y-m-d'),
            'due_date' => $invoice->due_date,
            'currency' => Currency::resolve($invoice->currency_id ?? $invoice->currency)?->currency,
            'total' => round((float) $invoice->total(), 2),
            'paid' => round((float) $invoice->paid, 2),
            'remaining' => round($invoice->unpaid_total(), 2),
            'client_name' => $invoice->user?->name,
            'project_name' => $invoice->project?->project_name,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'title' => (string) $item->item_title,
                'quantity' => (float) ($item->qty ?: 1),
                'unit_price' => (float) $item->amount,
                'total' => round($item->total(), 2),
            ])->values()->all(),
            'time_report' => self::timeReport($invoice),
        ];
    }

    /**
     * Hours worked on the invoice and what they would cost at full price. Null when no time was tracked.
     *
     * @return array<string, mixed>|null
     */
    private static function timeReport(Invoice $invoice): ?array
    {
        $metrics = (new InvoiceResource($invoice))->calculateTimerMetrics();
        if (($metrics['total_seconds'] ?? 0) <= 0) {
            return null;
        }

        $sessions = $invoice->items->flatMap(fn (InvoiceItem $item) => $item->timers->map(fn ($timer) => [
            'id' => $timer->id,
            'start' => $timer->date_start,
            'end' => $timer->date_end,
            'duration' => TextHelper::secondsToTime(self::seconds($timer)),
            'amount' => (float) $timer->amount,
        ]))->values()->all();

        return [
            'total_time' => $metrics['total_timer_str'],
            'billed' => $metrics['billed_amount_str'],
            'full_value' => $metrics['full_real_value_str'],
            'savings' => $metrics['has_discount'] ? $metrics['discount_savings_str'] : null,
            'market_value' => $metrics['market_hourly_rate'] > 0 ? $metrics['market_value_str'] : null,
            'sessions' => $sessions,
        ];
    }

    private static function seconds(object $timer): int
    {
        $secs = (int) $timer->diff();
        if ($secs > 0 || empty($timer->date_start) || empty($timer->date_end)) {
            return max(0, $secs);
        }

        return (int) abs(strtotime($timer->date_end) - strtotime($timer->date_start));
    }
}
