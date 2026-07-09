<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MergeUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'survivor_id'           => ['required', 'integer', 'exists:users,id'],
            'duplicate_ids'         => ['required_without:duplicate_id', 'array', 'min:1'],
            'duplicate_ids.*'       => ['integer', 'distinct', 'different:survivor_id', 'exists:users,id'],
            'resolutions'           => ['sometimes', 'array'],
            'resolutions.*'         => ['nullable'],
            // Backwards-compatible single duplicate field (kept for legacy callers).
            'duplicate_id'          => ['required_without:duplicate_ids', 'nullable', 'integer', 'different:survivor_id', 'exists:users,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function duplicateIds(): array
    {
        $ids = (array) $this->validated('duplicate_ids', []);
        if (!empty($ids)) {
            return array_values(array_unique(array_map('intval', $ids)));
        }
        $single = $this->validated('duplicate_id');
        return $single !== null ? [(int) $single] : [];
    }
}
