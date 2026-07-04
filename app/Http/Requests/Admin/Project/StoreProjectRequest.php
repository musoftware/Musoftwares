<?php

namespace App\Http\Requests\Admin\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'hour_rate' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['open', 'hold_on', 'closed'])],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'hide_future_tasks' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_end.after_or_equal' => __('general.project_date_end_after_start'),
        ];
    }
}
