<?php

namespace App\Http\Requests\Admin\UserLoan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
