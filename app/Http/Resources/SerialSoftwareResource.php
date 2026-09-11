<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SerialSoftware
 * @property \App\Models\SerialSoftware $resource
 */
class SerialSoftwareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => (bool) ($this->is_active ?? true),
            'default_status' => $this->default_status,
            'pricing_type' => $this->pricing_type ?? ($this->requires_payment ? 'single' : 'free'),
            'requires_payment' => (bool) $this->requires_payment,
            'show_price' => (bool) ($this->show_price ?? true),
            'show_whatsapp' => (bool) ($this->show_whatsapp ?? true),
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency ?? 'USD',
            'billing_cycle' => $this->billing_cycle ?? 'lifetime',
            'billing_days' => $this->billing_days,
            'whatsapp_number' => $this->whatsapp_number,
            'payment_instructions' => $this->payment_instructions,
            'total_devices' => $this->total_devices ?? 0,
            'active_count' => $this->active_count ?? 0,
            'inactive_count' => $this->inactive_count ?? 0,
            'blocked_count' => $this->blocked_count ?? 0,
            'packages_count' => $this->packages_count ?? $this->resource->packages()->count(),
            'created_at' => $this->created_at?->diffForHumans(),
            'created_at_full' => $this->created_at?->toDateTimeString(),
            'custom_keys' => $this->whenLoaded('customKeys'),
            'packages' => $this->whenLoaded('packages'),
        ];
    }
}
