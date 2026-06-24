<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:191'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'status'   => ['required', 'in:active,suspended,inactive'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
