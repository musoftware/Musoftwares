<?php

namespace App\Http\Requests\Admin\Sequence;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAISequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'context' => 'nullable|string',
            'num_steps' => 'required|integer|min:1|max:5',
            'tone' => 'required|string',
        ];
    }
}
