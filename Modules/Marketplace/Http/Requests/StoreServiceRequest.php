<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                    => 'required|string|max:255',
            'description'              => 'required|string|min:100',
            'category_id'              => 'required|exists:marketplace_service_categories,id',
            'tags'                     => 'nullable|array|max:5',
            'tags.*'                   => 'string|max:40',
            'video_url'                => 'nullable|url|max:255',
            'packages'                 => 'required|array|min:1|max:3',
            'packages.*.name'          => 'required|string|max:80',
            'packages.*.description'   => 'required|string|max:500',
            'packages.*.price'         => 'required|numeric|min:1',
            'packages.*.currency_id'   => 'required|integer|exists:currencies,id',
            'packages.*.delivery_days' => 'required|integer|min:1|max:365',
            'packages.*.revisions'     => 'nullable|integer|min:-1',
            'packages.*.features'      => 'nullable|array',
            'packages.*.features.*'    => 'string|max:60',
            'faq'                      => 'nullable|array|max:10',
            'faq.*.question'           => 'required|string|max:200',
            'faq.*.answer'             => 'required|string|max:1000',
            'requirements'             => 'nullable|array|max:10',
            'requirements.*'           => 'string|max:300',
            'gallery'                  => 'nullable|array|max:5',
            'gallery.*'                => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
