<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/** The worker's side of a project: take a paid job, report progress, hand back the program or the failure. */
class JobQueue
{
    private const PROGRESS_LEASE_MINUTES = 30;

    /** Copying the project into the build box is this much of the customer's bar; the engineer's work is the rest. */
    private const COPY_SHARE_OF_BAR = 5;

    public function __construct(
        private readonly DeliveryStore $store,
        private readonly ProjectPayment $payment,
        private readonly LiveTrials $trials,
        private readonly BuildCostHistory $costs,
    ) {}

    public function claim(): ?BuilderProject
    {
        $this->failExhausted();

        return DB::transaction(function (): ?BuilderProject {
            $project = BuilderProject::claimable((int) config('builder.limits.max_attempts'))->orderBy('paid_at')->lockForUpdate()->first();

            $project?->update([
                'status' => BuilderProject::BUILDING,
                'attempts' => $project->attempts + 1,
                'lease_expires_at' => now()->addMinutes((int) config('builder.limits.lease_minutes')),
                'progress_step' => 'started',
                'progress_file' => null,
            ]);

            return $project;
        });
    }

    /**
     * @param  int|null  $copied  how far the copy of the project into the build box is, in percent; only the copy sends it
     */
    public function progress(BuilderProject $project, string $step, ?string $file, ?int $copied = null): void
    {
        $lease = max($project->lease_expires_at?->getTimestamp() ?? 0, now()->addMinutes(self::PROGRESS_LEASE_MINUTES)->getTimestamp());

        $project->update(['progress_step' => $step, 'progress_file' => $file, 'lease_expires_at' => now()->setTimestamp($lease)] + $this->copyLine($project, $copied));
    }

    /**
     * A long copy shows the customer a line that moves, and a small part of the bar. The step after the copy clears
     * that line, so "copying 99%" never stays on the page while the engineer already works.
     *
     * @return array<string, mixed>
     */
    private function copyLine(BuilderProject $project, ?int $copied): array
    {
        if ($copied === null) {
            return $project->progress_step === 'importing' ? ['progress_note' => null] : [];
        }

        return [
            'progress_note' => __('builder.steps.copying', ['percent' => $copied], $project->customerLanguage()),
            'progress_percent' => max((int) $project->progress_percent, intdiv($copied * self::COPY_SHARE_OF_BAR, 100)),
        ];
    }

    public function complete(BuilderProject $project, ?string $zipPath, string $summary, float $costUsd, string $ideasJson = '', string $checksJson = ''): void
    {
        // an imported project's change lives in its folder on the build PC and its GitHub repo, not in a download
        $version = $zipPath !== null ? $this->store->store($project, $zipPath) : $project->version + 1;
        // this job's whole cost, failed tries included, teaches the next price and settles this one
        $jobCost = (float) $project->job_cost_usd + $costUsd;
        $this->costs->record($project, $jobCost);
        // the engineer writes for engineers; the customer reads the contact's plain retelling, the owner the original
        $forCustomer = app(Chat\CustomerVoice::class)->retell($project, $summary);

        DB::transaction(function () use ($project, $version, $forCustomer, $summary, $costUsd, $ideasJson, $checksJson, $jobCost): void {
            $project->update([
                'status' => BuilderProject::READY,
                'version' => $version,
                'built_at' => now(),
                // two months of free fixes start with the first delivery
                'warranty_until' => $project->warranty_until ?? now()->addDays((int) config('builder.warranty_days')),
                'lease_expires_at' => null,
                'progress_step' => null,
                'progress_file' => null,
                'progress_percent' => null,
                'progress_phases' => null,
                'progress_note' => null,
                'build_cost_usd' => (float) $project->build_cost_usd + $costUsd,
                'job_cost_usd' => 0,
                'auto_retries' => 0,
            ]);
            app(DeliveryStats::class)->record($project, $jobCost);
            $project->update(['owner_touched' => false]);
            $project->messages()->create([
                'role' => BuilderMessage::ASSISTANT,
                'content' => $forCustomer,
                'payload' => ['reply' => $forCustomer, 'engineer_note' => $summary, 'delivered_version' => $version, 'ideas' => $this->ideas($ideasJson), 'checks' => DeliveryChecks::fromJson($checksJson), 'needs_team' => false, 'quote' => ['ready' => false, 'title' => '', 'spec' => '', 'estimated_output_tokens' => 0]],
            ]);
        });
        // money back is good news, never a reason for a delivery to fail
        try {
            app(PriceSettlement::class)->settle($project->refresh(), $jobCost);
        } catch (\Throwable $e) {
            Log::error('App builder: price settlement failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
        app(BuilderPush::class)->notify($project, 'delivered');
        app(ProjectAccount::class)->afterDelivery($project);
        // an extension can only be tried in a browser, so its live trial starts by itself
        if ($this->trials->kind($project->refresh()) === 'extension') {
            $this->trials->request($project);
        }
        $this->payment->tellOwner($project, 'Delivered: '.$project->title.' v'.$version.' (cost '.$costUsd.' USD)'."\n\nEngineer: ".\Illuminate\Support\Str::limit($summary, 600));
    }

    public function fail(BuilderProject $project, string $error, float $costUsd): void
    {
        $project->update([
            'status' => BuilderProject::FAILED,
            'error' => mb_substr($error, 0, 4000),
            'lease_expires_at' => null,
            'build_cost_usd' => (float) $project->build_cost_usd + $costUsd,
            // kept for the try that finally delivers it, so the record shows the job's real cost
            'job_cost_usd' => (float) $project->job_cost_usd + $costUsd,
        ]);
        $this->payment->tellOwner($project, 'Build failed: '.$project->title.' - '.mb_substr($error, 0, 200));
        app(BuilderPush::class)->notify($project, 'failed');
    }

    /**
     * The engineer's ideas for the next version, kept small and plain: at most 4, title and one line each.
     *
     * @return list<array{title: string, description: string}>
     */
    private function ideas(string $json): array
    {
        $items = json_decode($json, true);
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($idea) => is_array($idea) && trim((string) ($idea['title'] ?? '')) !== '')
            ->take(4)
            ->map(fn (array $idea) => [
                'title' => mb_substr(trim(strip_tags((string) $idea['title'])), 0, 120),
                'description' => mb_substr(trim(strip_tags((string) ($idea['description'] ?? ''))), 0, 300),
            ])
            ->values()
            ->all();
    }

    /**
     * A failed build goes back to the worker. The worker starts from the project's folder on the drive, so the work
     * already done is kept and the build only finishes what is left.
     */
    public function requeue(BuilderProject $project): void
    {
        $project->update([
            'status' => BuilderProject::QUEUED,
            'attempts' => 0,
            'error' => null,
            'lease_expires_at' => null,
            'progress_step' => null,
            'progress_file' => null,
            'progress_note' => null,
        ]);
    }

    /** A job whose worker died on every try stops here, so the owner hears about it instead of a silent loop. */
    private function failExhausted(): void
    {
        BuilderProject::where('status', BuilderProject::BUILDING)
            ->where('lease_expires_at', '<', now())
            ->where('attempts', '>=', (int) config('builder.limits.max_attempts'))
            ->get()
            ->each(fn (BuilderProject $project) => $this->fail($project, 'The worker stopped answering on every try.', 0));
    }
}
