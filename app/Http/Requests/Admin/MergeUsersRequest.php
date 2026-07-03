<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MergeUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'survivor_id'        => ['required', 'integer', 'exists:users,id'],
            'duplicate_id'       => ['required', 'integer', 'different:survivor_id', 'exists:users,id'],
            'resolutions'        => ['sometimes', 'array'],
            'resolutions.*'      => ['nullable'],
        ];
    }
}
