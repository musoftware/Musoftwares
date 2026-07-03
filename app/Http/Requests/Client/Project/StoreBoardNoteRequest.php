<?php

namespace App\Http\Requests\Client\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is enforced by the policy on the parent project route-binding
    }

    public function rules(): array
    {
        return [
            'for_date' => ['required', 'string', 'date_format:Y-m-d'],
            'content' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'in:yellow,green,blue,red,purple,pink,slate'],
            'lane' => ['nullable', 'string', 'max:50'],
            'pos_x' => ['nullable', 'integer', 'min:0'],
            'pos_y' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
