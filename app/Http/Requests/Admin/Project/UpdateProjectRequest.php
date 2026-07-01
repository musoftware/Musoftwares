<?php

namespace App\Http\Requests\Admin\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'project_name' => ['sometimes', 'required', 'string', 'max:255'],
            'project_balance' => ['nullable', 'numeric'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:open,hold_on,closed'],
            'hide_future_tasks' => ['nullable', 'boolean'],
        ];
    }
}
