<?php

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Shop\Support\OptionList;

class ShopProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
            'sizes' => ['array', 'max:40'],
            'sizes.*' => ['string', 'max:50'],
            'colors' => ['array', 'max:40'],
            'colors.*' => ['string', 'max:50'],
            'in_stock' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'images_add' => ['nullable', 'array', 'max:12'],
            'images_add.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'images_remove' => ['nullable', 'array'],
            'images_remove.*' => ['string'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->requireImageWhenVisible($validator),
            fn (Validator $validator) => $this->keepPriceAboveAngazny($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sizes' => OptionList::parse($this->input('sizes')),
            'colors' => OptionList::parse($this->input('colors')),
            'sort_order' => (int) $this->input('sort_order'),
        ]);
    }

    /** Selling under Angazny's price would make the order lose money. */
    private function keepPriceAboveAngazny(Validator $validator): void
    {
        $floor = $this->route('product')?->angazny_price;

        if ($floor !== null && (float) $this->input('price') < (float) $floor) {
            $validator->errors()->add('price', 'السعر لازم يكون '.$floor.' جنيه أو أكتر، ده سعر انجزني');
        }
    }

    /** A product with no picture can stay saved, but it can't be shown in the store. */
    private function requireImageWhenVisible(Validator $validator): void
    {
        $current = $this->route('product')?->images ?? [];
        $kept = array_diff($current, $this->input('images_remove', []));
        $added = $this->file('images_add', []);

        if ($this->boolean('is_active') && $kept === [] && $added === []) {
            $validator->errors()->add('images_add', 'أضف صورة واحدة على الأقل عشان المنتج يظهر في المتجر');
        }
    }
}
