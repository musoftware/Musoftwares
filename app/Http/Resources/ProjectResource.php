<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Project
 */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currency = $this->currencyRow();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'owner_id' => $this->owner_id,
            'project_name' => $this->project_name,
            'description' => $this->description,
            // Logical, derivable financial metrics (no cached/fake "balance").
            'cost' => (string) $this->costAmount(),
            'paid_invoices' => (string) $this->paidInvoicesAmount(),
            'pending_invoices' => (string) $this->pendingInvoicesAmount(),
            'budget' => (string) ($this->budget ?? '0'),
            'total_paid' => (string) ($this->total_paid ?? '0'),
            'hour_rate' => (string) ($this->hour_rate ?? '0'),
            'percentage' => (float) ($this->percentage ?? 0),
            'status' => $this->status,
            'archived' => (bool) $this->archived,
            'archived_at' => optional($this->archived_at)->toIso8601String(),
            'hide_future_tasks' => (bool) $this->hide_future_tasks,
            'date_start' => optional($this->date_start)->toDateString(),
            'date_end' => optional($this->date_end)->toDateString(),
            'currency' => $currency ? [
                'id' => $currency->id,
                'currency' => $currency->currency,
                'symbol' => $currency->symbol,
                'string_format' => $currency->string_format,
            ] : null,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
            ]),
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ] : null),
            'counts' => [
                'contracts' => (int) ($this->contracts_count ?? 0),
                'invoices_unpaid' => (int) ($this->invoices_count ?? 0),
                'tasks' => (int) ($this->tasks_count ?? 0),
                'reports' => (int) ($this->reports_count ?? 0),
                'files' => (int) ($this->files_count ?? 0),
            ],
        ];
    }
}
