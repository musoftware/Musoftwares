<?php

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGatewayClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => 'sometimes|required|string|max:255',
            'website'         => 'nullable|url|max:255',
            'status'          => 'nullable|in:active,inactive',
            'allowed_ips'     => 'nullable|array',
            'allowed_ips.*'   => 'ip',
            'commission_rate' => 'nullable|numeric|min:1|max:100',
        ];
    }
}
