<?php

namespace App\Http\Requests\Admin\Security;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', 'max:255'],
            'tenant_id' => ['nullable', 'integer'],
            'ip_address' => ['nullable', 'ip'],
            'max_requests' => ['required', 'integer', 'min:1'],
            'decay_minutes' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
