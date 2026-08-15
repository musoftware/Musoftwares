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
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'string', 'url', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
