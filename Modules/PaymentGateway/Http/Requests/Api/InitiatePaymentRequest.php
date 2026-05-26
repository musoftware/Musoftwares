<?php

namespace Modules\PaymentGateway\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_id'        => 'required|string|max:255',
            'amount'          => 'required|numeric|min:1',
            'currency'        => 'nullable|string|size:3',
            'description'     => 'nullable|string|max:500',
            'success_url'     => 'required|url',
            'failure_url'     => 'nullable|url',
            'webhook_url'     => 'nullable|url',
            'customer'        => 'nullable|array',
            'customer.name'   => 'nullable|string|max:255',
            'customer.email'  => 'nullable|email|max:255',
            'customer.phone'  => 'nullable|string|max:20',
            'metadata'        => 'nullable|array',
        ];
    }
}
