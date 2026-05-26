<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'color'       => 'sometimes|string|max:7|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'sometimes|integer|min:0',
        ];
    }
}
