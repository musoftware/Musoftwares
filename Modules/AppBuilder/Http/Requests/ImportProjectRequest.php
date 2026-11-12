<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AppBuilder\Models\BuilderProject;

/** The owner's project to import: a shared Google Drive zip, its GitHub repo, and how its changes are released. */
class ImportProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'import_url' => ['required', 'url', 'max:2000', 'regex:#^https://(drive|docs)\.google\.com/#'],
            'github_repo' => ['nullable', 'string', 'max:200', 'regex:#^(https://)?github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?(\.git)?/?$#'],
            // a fine-grained token with "Contents" and "Pull requests" read and write on this one repo
            'github_token' => ['nullable', 'required_with:github_repo', 'string', 'max:300'],
            'locale' => ['required', 'in:ar,en'],
            'release_mode' => ['required', 'in:'.implode(',', [BuilderProject::RELEASE_EACH, BuilderProject::RELEASE_BATCH, BuilderProject::RELEASE_MANUAL])],
            'release_after_changes' => ['required', 'integer', 'min:1', 'max:50'],
            'release_quiet_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'deploy_enabled' => ['boolean'],
            'deploy_command' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_url.regex' => __('builder.admin.import_drive_only'),
            'github_repo.regex' => __('builder.admin.import_github_only'),
        ];
    }
}
