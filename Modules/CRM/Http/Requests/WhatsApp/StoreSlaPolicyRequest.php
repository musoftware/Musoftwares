<?php

namespace Modules\CRM\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class StoreSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'first_response_time' => 'required|integer|min:1|max:10080', // Max 7 days in minutes
            'resolution_time'     => 'required|integer|min:1|max:43200', // Max 30 days in minutes
            'priority'            => 'sometimes|string|in:low,medium,high,urgent',
            'business_hours_only' => 'sometimes|boolean',
            'notify_on_breach'    => 'sometimes|boolean',
            'escalation_user_id'  => 'nullable|integer|exists:users,id',
            'is_default'          => 'sometimes|boolean',
            'is_active'           => 'sometimes|boolean',
        ];
    }
}
