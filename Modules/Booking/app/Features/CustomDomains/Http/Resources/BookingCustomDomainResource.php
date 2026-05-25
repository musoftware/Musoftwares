<?php

namespace Modules\Booking\app\Features\CustomDomains\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingCustomDomainResource extends JsonResource
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
            'domain' => $this->domain,
            'status' => $this->status,
            'ssl_status' => $this->ssl_status,
            'verification_token' => $this->verification_token,
            'is_primary' => $this->is_primary,
            'connected_at' => $this->connected_at,
            'verified_at' => $this->verified_at,
            'last_checked_at' => $this->last_checked_at,
            'created_at' => $this->created_at,
        ];
    }
}
