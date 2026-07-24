<?php

namespace App\Http\Requests\Admin\CurrencyExchange;

use App\Models\CurrenciesExchange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreCurrencyExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_string' => ['required', 'date_format:Y-m-d'],
            'currency1' => ['required', 'integer', 'different:currency2', Rule::exists('currencies', 'id')],
            'currency2' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'rate' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function passedValidation(): void
    {
        $exists = CurrenciesExchange::withTrashed()
            ->where('date_string', $this->input('date_string'))
            ->where('currency1', $this->input('currency1'))
            ->where('currency2', $this->input('currency2'))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'date_string' => __('admin.exchange_already_exists_for_pair_on_date'),
            ]);
        }
    }
}
