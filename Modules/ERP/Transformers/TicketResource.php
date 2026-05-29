<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
            'assignee' => $this->assignee?->name ?? 'Unassigned',
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
