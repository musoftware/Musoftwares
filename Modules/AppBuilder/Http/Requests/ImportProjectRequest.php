<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * The owner's project to import: a shared Google Drive zip and how its changes are released. Its GitHub repo, access
 * and deploy steps come from the project's own files, so nothing else is asked.
 */
class ImportProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'import_url' => ['required', 'url', 'max:2000', 'regex:#^https://(drive|docs)\.google\.com/#'],
            'locale' => ['required', 'in:ar,en'],
            'release_mode' => ['required', 'in:'.implode(',', [BuilderProject::RELEASE_EACH, BuilderProject::RELEASE_BATCH, BuilderProject::RELEASE_MANUAL])],
            'release_after_changes' => ['required', 'integer', 'min:1', 'max:50'],
            'release_quiet_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'deploy_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_url.regex' => __('builder.admin.import_drive_only'),
        ];
    }
}
