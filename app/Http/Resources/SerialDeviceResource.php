<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SerialDevice
 * @property \App\Models\SerialDevice $resource
 */
class SerialDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'user_name' => $this->user_name,
            'machine_name' => $this->machine_name,
            'user_domain' => $this->user_domain,
            'serial_software_id' => $this->serial_software_id,
            'status' => $this->status,
            'os_version' => $this->os_version,
            'framework_version' => $this->framework_version,
            'is_64bit_os' => $this->is_64bit_os,
            'is_64bit_process' => $this->is_64bit_process,
            'current_directory' => $this->current_directory,
            'current_culture' => $this->current_culture,
            'current_ui_culture' => $this->current_ui_culture,
            'last_check_date' => $this->last_check_date?->diffForHumans(),
            'last_check_date_full' => $this->last_check_date?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'software' => $this->whenLoaded('software'),
            // Key matches frontend: device.userDeviceAssignment
            'userDeviceAssignment' => $this->whenLoaded('userDeviceAssignment', function () {
                $assignment = $this->userDeviceAssignment;
                if (! $assignment) {
                    return null;
                }

                return [
                    'id' => $assignment->id,
                    'status' => $assignment->status,
                    'expires_at' => $assignment->expires_at?->toDateString(),
                    'expires_at_formatted' => $assignment->expires_at?->format('Y-m-d H:i'),
                    'is_expired' => $assignment->isExpired(),
                    'remaining_days' => $assignment->expires_at ? max(0, (int) ceil(now()->diffInDays($assignment->expires_at, false))) : null,
                    'notes' => $assignment->notes,
                    'user' => $assignment->relationLoaded('user') && $assignment->user ? [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                        'email' => $assignment->user->email,
                    ] : null,
                ];
            }),
            'resolved_custom_keys' => $this->resource->getResolvedCustomKeys(),
            'device_keys' => $this->whenLoaded('deviceKeys'),
        ];
    }
}
