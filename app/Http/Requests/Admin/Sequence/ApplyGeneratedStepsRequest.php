<?php

namespace App\Http\Requests\Admin\Sequence;

use Illuminate\Foundation\Http\FormRequest;

class ApplyGeneratedStepsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'steps' => 'required|array'
        ];
    }
}
