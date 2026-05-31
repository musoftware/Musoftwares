<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_title' => $this->item_title,
            'item_type' => $this->item_type,
            'qty' => $this->qty,
            'amount' => $this->amount,
            'currency' => \App\Models\Currency::find($this->invoice?->currency)?->currency,
            'total_amount' => $this->total(),
            'created_at' => $this->created_at,
        ];
    }
}
