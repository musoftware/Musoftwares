<?php

namespace Modules\Booking\app\Features\MultiBranch\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingBranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'is_main_branch' => $this->is_main_branch,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Permissions metadata for UI integration
            'meta' => [
                'can_edit' => $request->user()?->can('update', $this->resource),
                'can_delete' => $request->user()?->can('delete', $this->resource),
                'can_assign_staff' => $request->user()?->can('assignStaff', $this->resource),
            ],
            
            // Optional loaded relations
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->pivot->role ?? 'staff',
                    ];
                });
            }),
        ];
    }
}
