<?php

namespace App\Http\Requests\Admin\Contract;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:draft,sent,signed,cancelled',
        ];
    }
}
