<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'provider'     => 'sometimes|string|in:baileys,cloud_api,waha',
            'is_default'   => 'sometimes|boolean',
        ];
    }
}
