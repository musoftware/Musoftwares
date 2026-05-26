<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuickReplyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'shortcut'  => 'required|string|max:50|starts_with:/',
            'title'     => 'required|string|max:255',
            'body'      => 'required|string|max:5000',
            'media_url' => 'nullable|url|max:500',
            'category'  => 'nullable|string|max:100',
            'is_global' => 'sometimes|boolean',
        ];
    }
}
