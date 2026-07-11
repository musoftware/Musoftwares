<?php

namespace App\Http\Resources;

use App\Helpers\CurrencyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'spend_amount' => (float) $this->spend_amount,
            'spend_currency' => CurrencyHelper::getFrontendCurrency($this->spend_currency),
            'reward_amount' => (float) $this->reward_amount,
            'reward_currency' => CurrencyHelper::getFrontendCurrency($this->reward_currency),
            'type' => $this->type,
            'reward_percentage' => (float) $this->reward_percentage,
            'max_uses_per_user' => $this->max_uses_per_user,
            'max_total_uses' => $this->max_total_uses,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'admin_notes' => $this->admin_notes,
            'redemptions_count' => $this->whenCounted('redemptions'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
