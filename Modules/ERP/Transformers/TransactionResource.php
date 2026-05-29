<?php

namespace Modules\ERP\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\AdminSettings;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        $businessCurrency = AdminSettings::business_currency();

        return [
            'id' => $this->id,
            'reference_id' => '#TXN-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'type' => $this->type,
            'note' => $this->note ?? 'No details provided',
            'direction' => strtoupper($this->direction),
            'amount' => round($this->amount, 2),
            'business_amount' => round($this->business_amount ?? $this->amount, 2),
            'currency' => $this->currency?->currency ?? $businessCurrency,
            'date' => $this->created_at?->format('Y-m-d H:i'),
            'authorizer' => $this->creator?->name ?? 'System Core',
        ];
    }
}
