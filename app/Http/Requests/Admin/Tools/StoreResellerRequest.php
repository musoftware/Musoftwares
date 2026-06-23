<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class StoreResellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'user_id'  => ['required', 'exists:users,id'],
            'name'     => ['required', 'string', 'max:191'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
