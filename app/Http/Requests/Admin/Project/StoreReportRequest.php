<?php

namespace App\Http\Requests\Admin\Project;

use App\Models\ProjectReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectReport::TYPES))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectReport::PRIORITIES))],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'published_at' => ['nullable', 'date'],
            'notify_client' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'notify_client' => $this->boolean('notify_client'),
        ]);
    }
}
