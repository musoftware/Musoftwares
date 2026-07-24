<?php

namespace App\Http\Requests\Admin\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'currency')->whereNull('deleted_at'),
            ],
            'symbol' => ['required', 'string', 'max:10'],
            'string_format' => ['required', 'string', 'max:20'],
        ];
    }
}
