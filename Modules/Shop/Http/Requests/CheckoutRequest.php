<?php

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shop\Services\Cart;

class CheckoutRequest extends FormRequest
{
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cart = app(Cart::class);

        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'governorate' => ['required', Rule::in($cart->governorates($cart->lines()))],
            'address' => ['required', 'string', 'min:8', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'angazny_area_id' => $this->areaRules($cart),
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
            'angazny_area_id.required' => 'اختار المنطقة',
            'angazny_area_id.in' => 'اختار منطقة من نفس المحافظة',
            'address.required' => 'اكتب العنوان',
            'address.min' => 'اكتب العنوان بالتفصيل',
        ];
    }

    /** @return array<string, string|null> */
    public function customer(): array
    {
        return $this->safe()->only(['customer_name', 'phone', 'governorate', 'address', 'notes', 'angazny_area_id']);
    }

    /** An area is asked for only when the cart ships through Angazny and the governorate has areas. */
    private function areaRules(Cart $cart): array
    {
        $areas = $cart->areas($cart->lines())[(string) $this->input('governorate')] ?? [];

        return $areas === [] ? ['prohibited'] : ['required', 'integer', Rule::in(array_column($areas, 'id'))];
    }

    protected function prepareForValidation(): void
    {
        $phone = str_replace(self::ARABIC_DIGITS, range(0, 9), (string) $this->input('phone'));

        $this->merge(['phone' => preg_replace('/[\s-]+/', '', $phone)]);
    }
}
