<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'spend_amount' => 'required|numeric|min:0',
            'spend_currency' => 'required|exists:currencies,id',
            'reward_amount' => 'required|numeric|min:0',
            'reward_currency' => 'required|exists:currencies,id',
            'type' => 'required|in:fixed,percentage',
            'reward_percentage' => 'nullable|numeric|min:0|max:100',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'max_total_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'admin_notes' => 'nullable|string',
        ];
    }
}
