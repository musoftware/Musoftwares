<?php

namespace Modules\AppBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\WorkerActivity;
use Modules\AppBuilder\Services\WorkerRoles;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes each worker call on a project to that project's log (what it took, each step, each picture, its questions,
 * the delivery or the failure), so the owner sees the build move. A refused call is written too. Logging never
 * breaks the call.
 */
class RecordWorkerActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        rescue(fn () => $this->record($request, $response), report: true);

        return $response;
    }

    private function record(Request $request, Response $response): void
    {
        $name = str_replace('builder.worker.', '', (string) $request->route()?->getName());
        $project = $this->projectOf($request, $response, $name);
        $describe = $this->details()[$name] ?? null;
        if ($project === null || $describe === null) {
            return;
        }

        $worker = app(WorkerRoles::class)->roleOf($request);
        $response->getStatusCode() >= 400
            ? app(WorkerActivity::class)->record($project, 'refused', $name.': '.$response->getStatusCode().' '.$this->message($response), $worker)
            : app(WorkerActivity::class)->record($project, $name, $describe($request, $response), $worker);
    }

    /** The project in the address; for a claim, the job the site just gave out. */
    private function projectOf(Request $request, Response $response, string $name): ?BuilderProject
    {
        $project = $request->route('project');
        if ($project instanceof BuilderProject) {
            return $project;
        }
        $uuid = $name === 'claim' ? ($this->json($response)['job']['uuid'] ?? null) : null;

        return $uuid === null ? null : BuilderProject::where('uuid', $uuid)->first();
    }

    /** @return array<string, Closure(Request, Response): ?string> the calls that are logged, and the line each one writes */
    private function details(): array
    {
        return [
            'claim' => fn (Request $request, Response $response) => ($this->json($response)['job']['kind'] ?? '').' · '.($this->json($response)['job']['title'] ?? ''),
            'source' => fn () => null,
            'chat' => fn () => null,
            'attachment' => fn (Request $request) => '#'.$request->route('attachment'),
            'progress' => fn (Request $request) => trim($request->input('step').' '.$request->input('file')),
            'snapshot' => fn (Request $request) => $request->input('percent').'% '.$request->input('caption').($request->hasFile('image') ? ' [picture]' : ''),
            'question' => fn (Request $request) => (string) $request->input('question'),
            'answers' => fn () => null,
            'complete' => fn (Request $request, Response $response) => 'v'.($this->json($response)['version'] ?? '?').' · $'.$request->input('cost_usd'),
            'fail' => fn (Request $request) => '$'.$request->input('cost_usd').' · '.$request->input('error'),
            'abandoned' => fn () => null,
            'imported' => fn (Request $request) => $request->input('github_repo'),
            'released' => fn (Request $request) => trim($request->input('pull_request_url').($request->boolean('deployed') ? ' [deployed]' : '')),
            'trials.started' => fn () => null,
            'trials.failed' => fn (Request $request) => $request->input('error'),
        ];
    }

    private function json(Response $response): array
    {
        return json_decode((string) $response->getContent(), true) ?: [];
    }

    private function message(Response $response): string
    {
        return (string) ($this->json($response)['message'] ?? '');
    }
}
