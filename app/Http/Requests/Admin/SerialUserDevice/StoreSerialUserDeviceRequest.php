<?php

namespace App\Http\Requests\Admin\SerialUserDevice;

use App\Models\SerialUserDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSerialUserDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'user_id'   => ['required', 'exists:users,id'],
            'device_id' => ['required', 'string', 'unique:serial_user_devices,device_id'],
            'status'    => ['required', 'string', Rule::in(SerialUserDevice::statuses())],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
