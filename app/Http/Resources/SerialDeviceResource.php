<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SerialDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'device_id'          => $this->device_id,
            'user_name'          => $this->user_name,
            'machine_name'       => $this->machine_name,
            'user_domain'        => $this->user_domain,
            'serial_software_id' => $this->serial_software_id,
            'status'             => $this->status,
            'last_check_date'    => $this->last_check_date,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            'software'           => $this->whenLoaded('software'),
            'user_assignment'    => $this->whenLoaded('userDeviceAssignment'),
        ];
    }
}
