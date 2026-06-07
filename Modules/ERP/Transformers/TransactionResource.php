<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ERP\Models\Tenant;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        if (!$this->currency) {
            throw new \Exception("Transaction is missing an associated currency relation.");
        }

        $tenantCurrencyModel = \App\Models\Currency::find($this->tenant?->base_currency_id);
        if (!$tenantCurrencyModel) {
            throw new \Exception("Tenant base currency not found.");
        }

        return [
            'id' => $this->id,
            'reference_id' => '#TXN-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'type' => $this->type,
            'note' => $this->note ?? 'No details provided',
            'direction' => strtoupper($this->direction),
            'amount' => round($this->amount, 2),
            'business_amount' => round($this->business_amount ?? $this->amount, 2),
            'currency' => $this->currency,
            'business_currency' => $tenantCurrencyModel,
            'date' => $this->created_at?->format('Y-m-d H:i'),
            'authorizer' => $this->creator?->name ?? 'System Core',
        ];
    }
}
