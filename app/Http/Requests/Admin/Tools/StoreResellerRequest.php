<?php

namespace App\Http\Requests\Admin\Tools;

use Illuminate\Foundation\Http\FormRequest;

class StoreResellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'  => ['required', 'exists:users,id'],
            'name'     => ['required', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:10'],
            'notes'    => ['nullable', 'string'],
        ];
    }
}
