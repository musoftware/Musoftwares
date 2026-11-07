<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * The admin form for a service: every seller field, plus the owner, the status and auto delivery.
 * The route group already limits it to admins.
 */
class AdminServiceRequest extends UpdateServiceRequest
{
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
