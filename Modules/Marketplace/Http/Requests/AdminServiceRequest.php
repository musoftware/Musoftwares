<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * The admin form for a service: every seller field, plus the owner, the status and auto delivery.
 * The route group already limits it to admins.
 */
class AdminServiceRequest extends UpdateServiceRequest
{
    private const TRANSLATED_FIELDS = ['title', 'tagline', 'description', 'auto_reply'];

    /** The plain column holds the English text, or the Arabic text when there is no English. */
    protected function prepareForValidation(): void
    {
        $plain = [];
        foreach (self::TRANSLATED_FIELDS as $field) {
            $translations = $this->input("{$field}_translations");
            if (is_array($translations)) {
                $plain[$field] = ($translations['en'] ?? null) ?: (($translations['ar'] ?? null) ?: null);
            }
        }

        $this->merge($plain);
        $this->fillPackageDetails();
    }

    public function rules(): array
    {
        return [
            'seller_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'status' => 'required|in:active,draft,suspended,rejected',
            'auto_complete' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'auto_reply' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => $this->boolean('auto_complete'))],
        ] + parent::rules();
    }

    public function messages(): array
    {
        return ['auto_reply.required' => __('admin.marketplace_auto_complete_needs_reply')];
    }
}
