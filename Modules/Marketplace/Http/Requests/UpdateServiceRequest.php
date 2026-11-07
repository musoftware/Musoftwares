<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Marketplace\Http\Requests\Concerns\FillsPackageDetails;
use Modules\Marketplace\Http\Requests\Concerns\FillsPlainTextFromTranslations;
use Modules\Marketplace\Http\Requests\Concerns\NormalizesRequirements;

class UpdateServiceRequest extends FormRequest
{
    use FillsPackageDetails;
    use FillsPlainTextFromTranslations;
    use NormalizesRequirements {
        prepareForValidation as normalizeRequirements;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeRequirements();
        $this->fillPlainTextFromTranslations();
        $this->fillPackageDetails();
    }

    public function authorize(): bool
    {
        return true; // We will use policy in controller instead.
    }

    public function rules(): array
    {
        return [
            'full_form'                => 'nullable|boolean',
            'title'                    => 'required|string|max:255',
            'title_translations'       => 'nullable|array',
            'tagline'                  => 'nullable|string|max:255',
            'tagline_translations'     => 'nullable|array',
            'description'              => 'required|string|min:10',
            'description_translations' => 'nullable|array',
            'auto_reply'               => 'nullable|string',
            'auto_reply_translations'  => 'nullable|array',
            'category_id'              => 'required|exists:marketplace_service_categories,id',
            'tags'                     => 'nullable|array|max:10',
            'tags.*'                   => 'string|max:40',
            'video_url'                => 'nullable|url|max:255',
            'is_free'                  => 'nullable|boolean',
            'service_link'             => 'nullable|string|max:2000',
            'generate_serials'         => 'nullable|boolean',
            'allow_random_serial'      => 'nullable|boolean',
            'validity_days'            => 'nullable|integer',
            'referral_commission_from' => 'nullable|string|in:fee,seller',
            'referral_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'extras'                   => 'nullable|array',
            'extras.*.id'              => 'nullable|integer',
            'extras.*.title'           => 'required|string|max:255',
            'extras.*.price'           => 'required|numeric|min:0',
            'extras.*.duration_days'   => 'nullable|integer|min:0',
            'packages'                 => 'required|array|min:1|max:3',
            'packages.*.id'            => 'nullable|integer',
            'packages.*.name'          => 'required|string|max:80',
            'packages.*.description'   => 'required|string|max:500',
            'packages.*.price'         => 'required|numeric|min:1',
            'packages.*.old_price'     => 'nullable|numeric|min:0',
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
            'gallery.*'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'kept_gallery'             => 'nullable|array',
            'kept_gallery.*'           => 'string',
            'gallery_order'            => 'nullable|array',
            'gallery_order.*'          => 'string',
        ];
    }
}
