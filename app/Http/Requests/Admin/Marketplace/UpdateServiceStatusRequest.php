<?php

namespace App\Http\Requests\Admin\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'status'           => 'required|in:active,declined,suspended,pending,rejected',
            'rejection_reason' => 'nullable|required_if:status,rejected|string|min:10',
        ];
    }
}
