<?php

namespace Modules\Booking\app\Features\TeamMembers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingTeamMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'job_title' => $this->job_title,
            'bio' => $this->bio,
            'is_bookable' => $this->is_bookable,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Permissions metadata for UI integration
            'meta' => [
                'can_edit' => $request->user()?->can('update', $this->resource),
                'can_delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
