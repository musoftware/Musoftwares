<?php

namespace App\Http\Requests\Admin\Contract;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_name' => 'required|string|min:3',
            'project_description' => 'nullable|string',
            'reference' => 'required|string',
            'prepared_by' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'duration' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'deposit_paid' => 'boolean',
            'currency_id' => 'required|integer',
            'status' => 'required|in:draft,sent,signed,active,completed',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'includes_hosting' => 'boolean',
            'hosting_duration' => 'nullable|string',
            'includes_support' => 'boolean',
            'support_duration' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'client_name' => 'nullable|string|max:255',
            'lang' => 'required|in:ar,en',
            'features' => 'nullable|array',
            'items' => 'nullable|array',
        ];
    }
}
