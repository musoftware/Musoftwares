<?php

namespace App\Http\Requests\Admin\SerialUserDevice;

use App\Models\SerialUserDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSerialStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SerialUserDevice::statuses())],
        ];
    }
}
