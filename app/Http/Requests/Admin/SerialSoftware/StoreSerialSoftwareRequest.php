<?php

namespace App\Http\Requests\Admin\SerialSoftware;

use App\Models\SerialSoftware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSerialSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255', 'unique:serial_softwares,name'],
            'default_status' => ['required', Rule::in(SerialSoftware::statuses())],
        ];
    }
}
