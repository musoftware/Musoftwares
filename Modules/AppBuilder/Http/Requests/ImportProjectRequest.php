<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * The owner's project to import and how its changes are released: either its GitHub repo address (the build PC
 * clones it with the owner's token) or a shared Google Drive zip. Access and deploy steps come from the project's own
 * files, so nothing else is asked.
 */
class ImportProjectRequest extends FormRequest
{
    /** A Google Drive link, or exactly https://github.com/owner/repo (nothing after the repo name). */
    private const SOURCE = '#^https://((drive|docs)\.google\.com/|github\.com/[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9_.-]{1,100}/?$)#';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'import_url' => ['required', 'url', 'max:2000', 'regex:'.self::SOURCE],
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
