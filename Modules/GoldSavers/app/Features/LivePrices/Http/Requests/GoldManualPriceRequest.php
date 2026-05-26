<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoldManualPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admin can set manual prices
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'price_gram_24k' => ['required', 'numeric', 'min:1', 'max:100000'],
            'price_usd_oz'   => ['nullable', 'numeric', 'min:0'],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0.0001'],
            'currency'       => ['nullable', 'string', 'in:EGP,USD,SAR,AED'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_gram_24k.required' => 'The 24K gram price is required.',
            'price_gram_24k.min'      => 'Price must be greater than 1.',
        ];
    }
}
