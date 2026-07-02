<?php

namespace Modules\Shortlink\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShortlinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_url' => ['required', 'string', 'url', 'max:5000'],
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
