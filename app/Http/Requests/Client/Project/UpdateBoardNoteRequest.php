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
            'content' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'in:yellow,green,blue,red,purple,pink'],
        ];
    }
}
