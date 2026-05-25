<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoldWatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'market_keys'         => ['nullable', 'array'],
            'market_keys.*'       => ['string', 'max:50'],
            'tracked_karats'      => ['nullable', 'array'],
            'tracked_karats.*'    => ['integer', 'in:24,21,18,14'],
            'tracked_currencies'  => ['nullable', 'array'],
            'tracked_currencies.*' => ['string', 'in:EGP,USD,SAR,AED'],
            'is_default'          => ['boolean'],
        ];
    }
}
