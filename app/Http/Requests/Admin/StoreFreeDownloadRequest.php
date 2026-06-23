<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFreeDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'Admin', 'superadmin']);
    }

    public function rules(): array
    {
        return [
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'programming_language' => 'nullable|string|max:64',
            'image'                => 'nullable|image|max:2048',
            'file'                 => 'nullable|file|max:512000', // 500MB max
            'is_active'            => 'boolean',
            'order_column'         => 'nullable|integer|min:0',
        ];
    }
}
