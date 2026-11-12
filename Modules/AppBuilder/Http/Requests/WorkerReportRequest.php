<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Progress, finished build or failure, sent by the worker. Which fields apply depends on the route. */
class WorkerReportRequest extends FormRequest
{
    public const STEPS = ['started', 'planning', 'writing', 'running', 'testing', 'fixing', 'reviewing', 'deploying', 'waiting', 'packaging', 'importing', 'releasing'];

    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'builder.worker.progress' => ['step' => ['required', 'in:'.implode(',', self::STEPS)], 'file' => ['nullable', 'string', 'max:200']],
            'builder.worker.complete' => self::release() + [
                // an imported project's changes go to its GitHub repo and server, not into a download
                'archive' => [$this->route('project')?->isOwnerImport() ? 'nullable' : 'required', 'file', 'max:'.(int) config('builder.limits.archive_kb')],
                'summary' => ['required', 'string', 'max:6000'],
                'cost_usd' => ['required', 'numeric', 'min:0', 'max:10000'],
                // ideas for the next version, as JSON: [{"title": "...", "description": "..."}]
                'ideas' => ['nullable', 'string', 'max:6000'],
                // an imported project's MUSOFTWARE.md after the change: what the program has now
                'study' => ['nullable', 'string', 'max:40000'],
            ],
            'builder.worker.imported' => [
                'profile' => ['required', 'string', 'max:20000'],
                // "owner/repo", read from the project's own .git folder
                'github_repo' => ['nullable', 'string', 'max:200', 'regex:#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#'],
                // a customer's own project: the report for them, and the maintenance work it needs, estimated
                'report' => ['nullable', 'string', 'max:6000'],
                'request_title' => ['nullable', 'string', 'max:255'],
                'request_spec' => ['nullable', 'string', 'max:10000'],
                'request_tokens' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'request_complex' => ['nullable', 'boolean'],
            ],
            'builder.worker.released' => self::release(),
            default => ['error' => ['required', 'string', 'max:8000'], 'cost_usd' => ['required', 'numeric', 'min:0', 'max:10000']],
        };
    }

    /** What a release did: pushed a pull request, deployed, and a short note for the owner. */
    private static function release(): array
    {
        return [
            'released' => ['nullable', 'boolean'],
            'pull_request_url' => ['nullable', 'url', 'max:500', 'starts_with:https://github.com/'],
            'deployed' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:4000'],
            // the build was delivered but its release did not go out
            'release_error' => ['nullable', 'string', 'max:4000'],
            // the app's new version, built by the release the way the project builds it
            'apk' => ['nullable', 'file', 'max:'.(int) config('builder.limits.archive_kb')],
        ];
    }
}
