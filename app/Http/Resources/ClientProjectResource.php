<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Project
 */
class ClientProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currency = $this->currencyRow();

        return [
            'id' => $this->id,
            'name' => $this->project_name,
            'status' => $this->status,
            'archived' => (bool) $this->archived,
            'percentage' => (float) ($this->percentage ?? 0),
            'date_start' => $this->date_start ? $this->date_start->toDateString() : null,
            'date_end' => $this->date_end ? $this->date_end->toDateString() : null,
            'hour_rate' => (float) ($this->hour_rate ?? 0),
            'budget' => (string) ($this->budget ?? 0),
            // Logical, derivable financial metrics (no cached/fake "balance").
            'cost' => (string) $this->costAmount(),
            'paid_invoices' => (string) $this->paidInvoicesAmount(),
            'pending_invoices' => (string) $this->pendingInvoicesAmount(),
            'total_paid' => (string) ($this->total_paid ?? 0),
            'hide_future_tasks' => (bool) $this->hide_future_tasks,
            'currency' => $currency ? [
                'id' => $currency->id,
                'currency' => $currency->currency,
                'symbol' => $currency->symbol,
                'string_format' => $currency->string_format,
            ] : null,
            'counts' => [
                'tasks' => $this->whenCounted('tasks', fn () => $this->tasks_count, fn () => $this->tasks()->count()),
                'reports' => $this->whenCounted('publishedReports', fn () => $this->published_reports_count, fn () => $this->publishedReports()->count()),
                'files' => $this->whenCounted('files', fn () => $this->files_count, fn () => $this->files()->count()),
            ],
        ];
    }
}
