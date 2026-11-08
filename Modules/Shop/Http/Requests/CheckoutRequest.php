<?php

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'governorate' => ['required', Rule::in(config('shop.governorates'))],
            'address' => ['required', 'string', 'min:8', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'اكتب اسمك',
            'customer_name.min' => 'اكتب اسمك كامل',
            'phone.required' => 'اكتب رقم موبايلك',
            'phone.regex' => 'رقم الموبايل لازم يكون 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015',
            'governorate.required' => 'اختار المحافظة',
            'governorate.in' => 'اختار المحافظة من القائمة',
            'address.required' => 'اكتب العنوان',
            'address.min' => 'اكتب العنوان بالتفصيل',
        ];
    }

    /** @return array<string, string|null> */
    public function customer(): array
    {
        return $this->safe()->only(['customer_name', 'phone', 'governorate', 'address', 'notes']);
    }

    protected function prepareForValidation(): void
    {
        $phone = str_replace(self::ARABIC_DIGITS, range(0, 9), (string) $this->input('phone'));

        $this->merge(['phone' => preg_replace('/[\s-]+/', '', $phone)]);
    }
}
