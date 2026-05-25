<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group' => 'required|string|max:255',
            'key'   => 'required|string|max:255|unique:language_lines,key,NULL,id,group,' . $this->group,
            'text'  => 'required|array',
        ];
    }
}
