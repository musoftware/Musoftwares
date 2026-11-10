<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderBuildCost;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Prices that learn from real builds. Claude estimates the code a build writes, but most of the cost is the
 * agent reading, testing and fixing, which no estimate knows in advance. So every delivered build records
 * what it really cost per estimated token, and the next price for that kind of project uses the median of
 * the latest builds. Until there are enough builds of a kind, the configured factor is used.
 */
class BuildCostHistory
{
    /** Saves a delivered paid build. Free fixes are left out: their price is not set by an estimate. */
    public function record(BuilderProject $project, float $jobCostUsd): void
    {
        $outputPrice = $this->outputPriceOf((string) $project->build_model);
        if ($project->free_fix || $project->estimated_tokens <= 0 || $jobCostUsd <= 0 || $outputPrice <= 0) {
            return;
        }

        BuilderBuildCost::create([
            'project_id' => $project->id,
            'project_type' => $project->project_type ?: QuotePricer::DEFAULT_TYPE,
            'model' => (string) $project->build_model,
            'estimated_tokens' => $project->estimated_tokens,
            'cost_usd' => round($jobCostUsd, 4),
            'work_factor' => round($jobCostUsd / ($project->estimated_tokens / 1_000_000 * $outputPrice), 3),
        ]);
    }

    /** The work factor for the next price of this kind of project. */
    public function workFactor(string $projectType): float
    {
        $learning = config('builder.pricing.learning');
        $factors = BuilderBuildCost::where('project_type', $projectType)->latest('id')->limit((int) $learning['samples'])->pluck('work_factor')
            ->map(fn ($factor) => (float) $factor)->sort()->values();

        if ($factors->count() < (int) $learning['min_samples']) {
            return (float) config('builder.pricing.work_factor');
        }

        // the median ignores one build that went very well or very badly; the limits stop a broken record
        return round(min((float) $learning['max_factor'], max((float) $learning['min_factor'], (float) $factors->median())), 3);
    }

    /** @return list<array{type: string, builds: int, factor: float, learned: bool}> what the prices have learned so far */
    public function summary(): array
    {
        $minimum = (int) config('builder.pricing.learning.min_samples');

        return BuilderBuildCost::query()->selectRaw('project_type, count(*) as builds')->groupBy('project_type')->orderBy('project_type')->get()
            ->map(fn ($row) => [
                'type' => (string) $row->project_type,
                'builds' => (int) $row->builds,
                'factor' => $this->workFactor((string) $row->project_type),
                'learned' => (int) $row->builds >= $minimum,
            ])->all();
    }

    private function outputPriceOf(string $model): float
    {
        $match = collect((array) config('builder.build_models'))->firstWhere('model', $model);

        return (float) ($match['output_usd_per_mtok'] ?? 0);
    }
}
