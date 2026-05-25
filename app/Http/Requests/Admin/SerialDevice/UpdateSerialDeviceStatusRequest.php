<?php

namespace App\Http\Requests\Admin\SerialDevice;

use App\Models\SerialDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSerialDeviceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SerialDevice::statuses())],
        ];
    }
}
