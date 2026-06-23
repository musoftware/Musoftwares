<?php

namespace App\Http\Requests\Admin\SerialUserDevice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserTempValidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'temp_valid_until' => ['nullable', 'date'],
        ];
    }
}
