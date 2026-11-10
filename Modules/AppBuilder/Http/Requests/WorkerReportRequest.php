<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Progress, finished build or failure, sent by the worker. Which fields apply depends on the route. */
class WorkerReportRequest extends FormRequest
{
    public const STEPS = ['started', 'planning', 'writing', 'running', 'testing', 'fixing', 'reviewing', 'deploying', 'waiting', 'packaging'];

    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'builder.worker.progress' => ['step' => ['required', 'in:'.implode(',', self::STEPS)], 'file' => ['nullable', 'string', 'max:200']],
            'builder.worker.complete' => [
                'archive' => ['required', 'file', 'max:'.(int) config('builder.limits.archive_kb')],
                'summary' => ['required', 'string', 'max:6000'],
                'cost_usd' => ['required', 'numeric', 'min:0', 'max:10000'],
                // ideas for the next version, as JSON: [{"title": "...", "description": "..."}]
                'ideas' => ['nullable', 'string', 'max:6000'],
            ],
            default => ['error' => ['required', 'string', 'max:8000'], 'cost_usd' => ['required', 'numeric', 'min:0', 'max:10000']],
        };
    }
}
