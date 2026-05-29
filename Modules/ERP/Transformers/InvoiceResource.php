<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'amount' => round((float) $this->amount, 2),
            'business_amount' => round((float) $this->business_amount, 2),
            'currency' => $this->amount_currency,
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
