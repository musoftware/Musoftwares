<?php

namespace App\Http\Requests\Admin\Sequence;

use Illuminate\Foundation\Http\FormRequest;

class StoreSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'is_active' => 'boolean',
        ];
    }
}
