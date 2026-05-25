<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')->id;

        return [
            'code'                => 'required|string|max:50|unique:coupons,code,' . $couponId,
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'type'                => 'required|in:fixed,percentage',
            'discount_amount'     => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'currency'            => 'required|exists:currencies,id',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_uses_per_user'   => 'nullable|integer|min:1',
            'max_total_uses'      => 'nullable|integer|min:1',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after:starts_at',
            'is_active'           => 'boolean',
            'admin_notes'         => 'nullable|string',
        ];
    }
}
