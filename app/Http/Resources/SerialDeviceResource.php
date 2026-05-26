<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'device_id'             => $this->device_id,
            'user_name'             => $this->user_name,
            'machine_name'          => $this->machine_name,
            'user_domain'           => $this->user_domain,
            'serial_software_id'    => $this->serial_software_id,
            'status'                => $this->status,
            'os_version'            => $this->os_version,
            'framework_version'     => $this->framework_version,
            'is_64bit_os'           => $this->is_64bit_os,
            'is_64bit_process'      => $this->is_64bit_process,
            'current_directory'     => $this->current_directory,
            'current_culture'       => $this->current_culture,
            'current_ui_culture'    => $this->current_ui_culture,
            'last_check_date'       => $this->last_check_date?->diffForHumans(),
            'last_check_date_full'  => $this->last_check_date?->toDateTimeString(),
            'created_at'            => $this->created_at?->toDateString(),
            'updated_at'            => $this->updated_at?->toDateTimeString(),
            'software'              => $this->whenLoaded('software'),
            // Key matches frontend: device.userDeviceAssignment
            'userDeviceAssignment'  => $this->whenLoaded('userDeviceAssignment'),
        ];
    }
}
