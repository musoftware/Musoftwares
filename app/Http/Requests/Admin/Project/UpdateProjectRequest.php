<?php

namespace App\Http\Requests\Admin\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'project_name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'hour_rate' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['open', 'hold_on', 'closed'])],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'hide_future_tasks' => ['nullable', 'boolean'],
            'show_on_landing_portfolio' => ['nullable', 'boolean'],
            'portfolio_category' => ['nullable', 'string', 'max:32'],
            'portfolio_title' => ['nullable', 'string', 'max:255'],
            'portfolio_description' => ['nullable', 'string', 'max:5000'],
            'portfolio_tech' => ['nullable', 'array'],
            'portfolio_live_url' => ['nullable', 'url', 'max:512'],
            'portfolio_github_url' => ['nullable', 'url', 'max:512'],
            'portfolio_sort_order' => ['nullable', 'integer', 'min:0'],
            'portfolio_image_file' => ['nullable', 'image', 'max:15360'],
        ];
    }
}
