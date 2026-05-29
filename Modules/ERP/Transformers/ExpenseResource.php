<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => round((float) $this->amount, 2),
            'business_amount' => round((float) $this->business_amount, 2),
            'currency' => $this->currency?->currency ?? 'USD',
            'date' => $this->created_at?->format('Y-m-d'),
            'payer' => $this->payer?->name ?? 'System',
        ];
    }
}
