<?php

namespace App\Http\Requests\Admin\SerialSoftware;

use App\Models\SerialSoftware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSerialSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:serial_softwares,name'],
            'default_status' => ['required', Rule::in(SerialSoftware::statuses())],
            'requires_payment' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
