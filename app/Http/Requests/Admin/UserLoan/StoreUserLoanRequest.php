<?php

namespace App\Http\Requests\Admin\UserLoan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
