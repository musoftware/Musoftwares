<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'budget' => round((float) $this->budget, 2),
            'deadline' => $this->due_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d'),
            'client' => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
            ] : null,
            'leader' => $this->creator?->name ?? '-',
            'currency' => $this->currency ? [
                'id' => $this->currency->id,
                'currency' => $this->currency->currency,
            ] : null,
        ];
    }
}
