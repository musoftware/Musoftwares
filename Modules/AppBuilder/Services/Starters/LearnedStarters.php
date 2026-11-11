<?php

namespace Modules\AppBuilder\Services\Starters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderStarter;

/**
 * Every paid project, once delivered, becomes an internal ready program: when the next customer wants the same system,
 * the chat prices it at what it was sold for and the build starts from its code (copied on the build machine, cleaned
 * of the first customer). The customer is never shown or told. A newer paid delivery updates its card. Runs on a
 * schedule, a few projects at a time.
 */
class LearnedStarters
{
    private const PER_RUN = 5;

    public function __construct(private readonly StarterCardWriter $writer) {}

    /** @return int how many programs were added or updated */
    public function learnFromDeliveries(): int
    {
        $learned = 0;
        foreach ($this->due()->limit(self::PER_RUN)->get() as $project) {
            $learned += $this->learn($project) ? 1 : 0;
        }

        return $learned;
    }

    /** Paid, delivered, not a free build, and not learned at its current version yet (or removed by the owner). */
    private function due(): Builder
    {
        return BuilderProject::query()
            ->where('version', '>', 0)
            ->where('paid_total', '>', 0)
            ->where('free_build', false)
            ->whereNotExists(fn ($learned) => $learned->from('builder_starters')
                ->whereColumn('builder_starters.source_project_id', 'builder_projects.id')
                ->where(fn ($done) => $done->whereColumn('builder_starters.version', '>=', 'builder_projects.version')
                    ->orWhereNotNull('builder_starters.deleted_at')))
            ->orderBy('built_at');
    }

    private function learn(BuilderProject $project): bool
    {
        try {
            $card = $this->writer->write("Kind of project: {$project->project_type}\n\n".$project->spec);
        } catch (\RuntimeException $e) {
            Log::warning('App builder: no ready program from this delivery yet', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return false;
        }

        $starter = BuilderStarter::firstOrNew(['source_project_id' => $project->id]);
        $starter->slug ??= 'learned-'.Str::lower(Str::substr($project->uuid, 0, 8));
        $starter->fill([
            'version' => $project->version,
            'type' => $project->project_type ?: 'other',
            'title' => Str::limit($card['title'], 120, ''),
            'summary' => $card['summary'],
            'features' => array_slice($card['features'], 0, 10),
            // the known price: what this program was sold for
            'price' => $project->paid_total,
            'currency' => $project->currency,
        ])->save();

        return true;
    }
}
