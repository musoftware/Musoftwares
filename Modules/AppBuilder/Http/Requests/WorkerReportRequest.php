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
                'archive' => [$this->route('project')?->isImported() ? 'nullable' : 'required', 'file', 'max:'.(int) config('builder.limits.archive_kb')],
                'summary' => ['required', 'string', 'max:6000'],
                'cost_usd' => ['required', 'numeric', 'min:0', 'max:10000'],
                // ideas for the next version, as JSON: [{"title": "...", "description": "..."}]
                'ideas' => ['nullable', 'string', 'max:6000'],
            ],
            'builder.worker.imported' => ['profile' => ['required', 'string', 'max:20000']],
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
        ];
    }
}
