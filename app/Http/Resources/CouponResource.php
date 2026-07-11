<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'discount_amount' => (float) $this->discount_amount,
            'discount_percentage' => (float) $this->discount_percentage,
            'currency_id' => $this->currency,
            'min_purchase_amount' => (float) $this->min_purchase_amount,
            'max_uses_per_user' => $this->max_uses_per_user,
            'max_total_uses' => $this->max_total_uses,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'admin_notes' => $this->admin_notes,
            'currency_relation' => $this->whenLoaded('currencyRelation', function () {
                return [
                    'id' => $this->currencyRelation->id,
                    'currency' => $this->currencyRelation->currency,
                ];
            }),
            'redemptions_count' => $this->whenCounted('redemptions'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
