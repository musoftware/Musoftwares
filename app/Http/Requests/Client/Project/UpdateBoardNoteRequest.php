<?php

namespace App\Http\Requests\Client\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoardNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:61440'],
            'color' => ['nullable', 'string', 'in:yellow,green,blue,red,purple,pink,slate'],
        ];
    }
}
