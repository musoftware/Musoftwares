<?php

namespace App\Http\Requests\Admin\SerialUserDevice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserTempValidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'temp_valid_until' => ['nullable', 'date'],
        ];
    }
}
