<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_whatsapp' => ['required', 'string', 'max:50', 'regex:/^[0-9+\s\-]{7,50}$/'],
            // the share of the price paid now; only on the first payment of a quote
            'share' => ['nullable', 'integer', 'in:'.implode(',', (array) config('builder.deposit_shares'))],
        ];
    }
}
