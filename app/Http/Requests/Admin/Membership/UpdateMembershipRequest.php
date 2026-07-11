<?php

namespace App\Http\Requests\Admin\Membership;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $membershipId = $this->route('membership')->id;

        return [
            'name' => 'required|string|max:255|unique:memberships,name,'.$membershipId,
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|integer|exists:currencies,id',
            'color_hue_degree' => 'nullable|integer|min:0|max:360',
            'is_active' => 'boolean',
            'software_programs' => 'nullable|array',
            'software_programs.*' => 'exists:software_programs,id',
        ];
    }
}
