<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:10'],
            'status'   => ['required', 'in:active,suspended,inactive'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
