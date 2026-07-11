<?php

namespace App\Http\Resources;

use App\Helpers\CurrencyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,

            // Real DB columns — mapped properly
            'type' => $this->type,
            'type_name' => $this->type_name(),
            'summary' => $this->method_data(),    // short label: bank name, mobile, email, etc.
            'details' => $this->method_details(), // full formatted detail string

            // Raw fields for Show page deep-dive
            'bank_name' => $this->bank_name,
            'bank_number' => $this->bank_number,
            'bank' => $this->bank,
            'bank_branch' => $this->bank_branch,
            'mobile' => $this->mobile,
            'payee_email' => $this->payee_email,
            'ewallet_provider' => $this->ewallet_provider,
            'id_number' => $this->id_number,
            'currency' => CurrencyHelper::getFrontendCurrency($this->currency_id),

            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ] : null;
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
