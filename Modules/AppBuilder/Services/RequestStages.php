<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * Where each started request stands, stage by stage, as the owner reads it on the admin page: the work, the build,
 * the tests, the push to GitHub and the deploy. Each stage is one of: yes, no, now, waiting, none (the project does
 * not have that stage) or unknown (it happened before the site kept a record of it).
 */
class RequestStages
{
    private const NOT_STARTED = ['work' => 'waiting', 'build' => 'waiting', 'tests' => 'waiting', 'tests_note' => null];

    /** @var array<int, list<array{passed: bool}>> what was tried before each delivered version */
    private array $checks;

    private int $deployedVersion;

    public function __construct(private readonly BuilderProject $project)
    {
        $deliveries = $project->messages()->whereIn('role', [BuilderMessage::ASSISTANT, BuilderMessage::TEAM])->whereNotNull('payload')->get();
        $this->checks = $deliveries->filter(fn (BuilderMessage $message) => isset($message->payload['checks'], $message->payload['delivered_version']))
            ->mapWithKeys(fn (BuilderMessage $message) => [(int) $message->payload['delivered_version'] => (array) $message->payload['checks']])->all();
        $this->deployedVersion = (int) $deliveries->max(fn (BuilderMessage $message) => (int) ($message->payload['deployed_version'] ?? 0));
    }

    /** @return array{work: string, build: string, tests: string, tests_note: string|null, push: string, deploy: string} */
    public function of(BuilderRequest $request): array
    {
        $version = $request->status === BuilderRequest::DONE ? (int) $request->built_in_version : 0;

        return $this->made($request, $version) + ['push' => $this->push($version), 'deploy' => $this->deploy($version)];
    }

    /** @return array{work: string, build: string, tests: string, tests_note: string|null} */
    private function made(BuilderRequest $request, int $version): array
    {
        if ($request->status === BuilderRequest::DONE) {
            $checks = $this->checks[$version] ?? [];
            $passed = count(array_filter($checks, fn ($check) => ($check['passed'] ?? false) === true));

            // a build is delivered only after the program's own tests pass
            return ['work' => $request->rolled_back_at ? 'rolled_back' : 'yes', 'build' => 'yes', 'tests' => $checks === [] || $passed === count($checks) ? 'yes' : 'no', 'tests_note' => $checks === [] ? null : $passed.'/'.count($checks)];
        }
        if ($request->status !== BuilderRequest::BUILDING) {
            return self::NOT_STARTED;
        }
        if ($this->project->status !== BuilderProject::FAILED) {
            return ['work' => 'now'] + self::NOT_STARTED;
        }
        // the build stopped: at the final check the code was written and its tests failed, otherwise the build itself failed
        $atTests = str_contains((string) $this->project->error, 'final check');

        return ['work' => 'no', 'build' => $atTests ? 'yes' : 'no', 'tests' => $atTests ? 'no' : 'waiting', 'tests_note' => null];
    }

    private function push(int $version): string
    {
        if (! $this->project->isOwnerImport() || ! $this->project->github_repo) {
            return 'none';
        }

        return $version > 0 && $version <= (int) $this->project->released_version ? 'yes' : 'waiting';
    }

    private function deploy(int $version): string
    {
        if (! $this->project->isOwnerImport() || ! $this->project->deploy_enabled) {
            return 'none';
        }
        if ($version > 0 && $version <= $this->deployedVersion) {
            return 'yes';
        }

        // released before the site kept which release reached the server
        return $version > 0 && $version <= (int) $this->project->released_version ? 'unknown' : 'waiting';
    }
}
