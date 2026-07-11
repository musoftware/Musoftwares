<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectBoardCategory;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Builds per-day board cards (notes + tasks + reports + todos + files merged with their saved placements).
 * Shared by the client calendar (applies future-task gating) and the admin project board
 * (sees everything, no gating).
 */
class ProjectBoardService
{
    /** @return string[] */
    public function lanes(): array
    {
        return ['backlog', 'in_progress', 'review', 'done'];
    }

    /**
     * Ensure a project has the canonical set of system categories. Idempotent —
     * existing rows with the same slug are skipped, so re-running is safe.
     *
     * @return Collection<int, ProjectBoardCategory>
     */
    public function ensureDefaultCategories(Project $project)
    {
        return DB::transaction(function () use ($project) {
            $existing = $project->boardCategories()
                ->whereIn('slug', array_column(ProjectBoardCategory::DEFAULTS, 'slug'))
                ->pluck('slug')
                ->all();

            foreach (ProjectBoardCategory::DEFAULTS as $row) {
                if (in_array($row['slug'], $existing, true)) {
                    continue;
                }
                $project->boardCategories()->create(array_merge($row, [
                    'is_system' => true,
                ]));
            }

            return $project->boardCategories()->ordered()->get();
        });
    }

    /** Ordered categories for the board UI; lazily seeds defaults when empty. */
    public function categoriesFor(Project $project)
    {
        $categories = $project->boardCategories()->ordered()->get();
        if ($categories->isEmpty()) {
            $categories = $this->ensureDefaultCategories($project);
        }

        return $categories;
    }

    /**
     * Default per-(user, project) board UI preferences. Returned for any caller
     * (admins, clients, guests) so the frontend can hydrate the toolbar before
     * the user has ever saved a preference.
     *
     * @return array{view_mode: string, sort_by: string, sort_dir: string}
     */
    public function defaultPreferences(): array
    {
        return [
            'view_mode' => ProjectBoardPreference::VIEW_CARDS,
            'sort_by' => ProjectBoardPreference::SORT_MANUAL,
            'sort_dir' => ProjectBoardPreference::DIR_ASC,
        ];
    }

    /**
     * Resolve the saved preference row for a (user, project) pair, falling back
     * to `defaultPreferences()` when no row exists or the user is a guest.
     *
     * @return array{view_mode: string, sort_by: string, sort_dir: string}
     */
    public function getPreference(?User $user, Project $project): array
    {
        if (! $user) {
            return $this->defaultPreferences();
        }

        $row = ProjectBoardPreference::query()
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->first();

        if (! $row) {
            return $this->defaultPreferences();
        }

        return [
            'view_mode' => $row->view_mode,
            'sort_by' => $row->sort_by,
            'sort_dir' => $row->sort_dir,
        ];
    }

    /**
     * Persist the user's board view/sort preference. Validates against the
     * canonical whitelists on ProjectBoardPreference so arbitrary column names
     * can't be smuggled into the DB.
     *
     * @param  array{view_mode?: string, sort_by?: string, sort_dir?: string}  $data
     * @return array{view_mode: string, sort_by: string, sort_dir: string}
     */
    public function setPreference(User $user, Project $project, array $data): array
    {
        $validated = validator($data, [
            'view_mode' => ['sometimes', 'string', Rule::in(ProjectBoardPreference::VIEWS)],
            'sort_by' => ['sometimes', 'string', Rule::in(ProjectBoardPreference::SORTS)],
            'sort_dir' => ['sometimes', 'string', Rule::in(ProjectBoardPreference::DIRS)],
        ])->validate();

        $current = $this->getPreference($user, $project);

        $next = array_merge($current, array_intersect_key($validated, $current));

        ProjectBoardPreference::query()->updateOrCreate(
            ['user_id' => $user->id, 'project_id' => $project->id],
            $next,
        );

        return $next;
    }

    /**
     * Sort a flat list of board cards by the user's chosen key/direction.
     * "manual" returns the input untouched so the caller's drag-drop order wins.
     * Anything else returns a freshly sorted copy — never mutates the input.
     *
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    public function applySort(array $cards, string $sortBy, string $sortDir): array
    {
        if ($sortBy === ProjectBoardPreference::SORT_MANUAL || empty($cards)) {
            return $cards;
        }

        $dir = strtolower($sortDir) === ProjectBoardPreference::DIR_DESC ? -1 : 1;
        $laneOrder = ['backlog' => 0, 'in_progress' => 1, 'review' => 2, 'done' => 3];
        $typeOrder = ['note' => 0, 'task' => 1, 'todo' => 2, 'report' => 3, 'file' => 4];
        $priorityOrder = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];

        $key = $sortBy;

        usort($cards, function ($a, $b) use ($key, $dir, $laneOrder, $typeOrder, $priorityOrder) {
            $cmp = 0;
            switch ($key) {
                case ProjectBoardPreference::SORT_TITLE:
                    $cmp = strcmp(
                        mb_strtolower((string) ($a['title'] ?? '')),
                        mb_strtolower((string) ($b['title'] ?? '')),
                    );
                    break;
                case ProjectBoardPreference::SORT_TYPE:
                    $ta = $typeOrder[$a['type'] ?? ''] ?? 99;
                    $tb = $typeOrder[$b['type'] ?? ''] ?? 99;
                    $cmp = $ta <=> $tb;
                    if ($cmp === 0) {
                        $cmp = strcmp(
                            mb_strtolower((string) ($a['title'] ?? '')),
                            mb_strtolower((string) ($b['title'] ?? '')),
                        );
                    }
                    break;
                case ProjectBoardPreference::SORT_LANE:
                    $la = $laneOrder[$a['lane'] ?? ''] ?? 99;
                    $lb = $laneOrder[$b['lane'] ?? ''] ?? 99;
                    $cmp = $la <=> $lb;
                    if ($cmp === 0) {
                        $sa = (int) ($a['sort'] ?? 0);
                        $sb = (int) ($b['sort'] ?? 0);
                        $cmp = $sa <=> $sb;
                    }
                    break;
                case ProjectBoardPreference::SORT_PRIORITY:
                    $pa = $priorityOrder[$a['priority'] ?? ''] ?? 99;
                    $pb = $priorityOrder[$b['priority'] ?? ''] ?? 99;
                    $cmp = $pa <=> $pb;
                    if ($cmp === 0) {
                        $cmp = strcmp(
                            mb_strtolower((string) ($a['title'] ?? '')),
                            mb_strtolower((string) ($b['title'] ?? '')),
                        );
                    }
                    break;
                case ProjectBoardPreference::SORT_CATEGORY:
                    $ca = $a['category_id'] ?? null;
                    $cb = $b['category_id'] ?? null;
                    // Nulls always sort to the end regardless of direction.
                    if ($ca === null && $cb === null) {
                        $cmp = 0;
                    } elseif ($ca === null) {
                        return 1;
                    } elseif ($cb === null) {
                        return -1;
                    } else {
                        $cmp = $ca <=> $cb;
                    }
                    if ($cmp === 0) {
                        $cmp = strcmp(
                            mb_strtolower((string) ($a['title'] ?? '')),
                            mb_strtolower((string) ($b['title'] ?? '')),
                        );
                    }
                    break;
                default:
                    return 0;
            }

            if ($cmp === 0) {
                $cmp = strcmp(
                    (string) ($a['type'] ?? '').':'.(int) ($a['id'] ?? 0),
                    (string) ($b['type'] ?? '').':'.(int) ($b['id'] ?? 0),
                );
            }

            return $cmp * $dir;
        });

        return $cards;
    }

    /**
     * @param  array{view_mode?: string, sort_by?: string, sort_dir?: string}|null  $preferences
     * @return array<int, array<string, mixed>>
     */
    public function cardsForDate(Project $project, Carbon $date, bool $applyFutureGating, ?array $preferences = null): array
    {
        // Clients with the flag enabled never see a board for a future date.
        if ($applyFutureGating && $project->hide_future_tasks && $date->isAfter(Carbon::today())) {
            return [];
        }

        $notes = $project->boardNotes()
            ->withCount('comments')
            ->whereDate('for_date', $date->toDateString())
            ->get();

        $tasks = $project->tasks()
            ->withCount('comments')
            ->whereDate('due_date', $date->toDateString())
            ->get();

        // Clients only see published reports; admins see all reports for the date.
        $reportsQuery = $applyFutureGating ? $project->publishedReports() : $project->reports();
        $reports = $reportsQuery->withCount('comments')->whereDate('published_at', $date->toDateString())->get();

        $dateString = $date->toDateString();

        $todos = $project->todos()
            ->withCount('comments')
            ->where(function ($q) use ($dateString) {
                $q->whereDate('inDate', $dateString)
                    ->orWhere(function ($q2) use ($dateString) {
                        $q2->whereNull('inDate')
                            ->whereDate('created_at', $dateString);
                    });
            })
            ->get();

        $files = $project->files()
            ->withCount('comments')
            ->whereDate('created_at', $date->toDateString())
            ->get();

        $cards = $this->buildCards($project, $date, $notes, $tasks, $reports, $todos, $files);

        // `buildCards()` appends in a fixed type-grouped order (notes → tasks → reports
        // → todos → files). That's fine for building, but the UI displays cards grouped
        // by status lane and ordered by the persisted `sort` column. Sort here so that
        // the initial render matches the order the user expects (and the order written
        // back by the drag-drop reorder endpoint).
        $laneOrder = ['backlog' => 0, 'in_progress' => 1, 'review' => 2, 'done' => 3];
        usort($cards, function ($a, $b) use ($laneOrder) {
            $la = $laneOrder[$a['lane']] ?? 99;
            $lb = $laneOrder[$b['lane']] ?? 99;
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            $sa = $a['sort'] ?? PHP_INT_MAX;
            $sb = $b['sort'] ?? PHP_INT_MAX;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return strcmp(($a['type'] ?? '').':'.($a['id'] ?? 0), ($b['type'] ?? '').':'.($b['id'] ?? 0));
        });

        // Apply the per-viewer's sort preference last so it wins over the lane/sort
        // baseline above. Manual sort is a no-op (the manual drag-drop order is
        // already encoded in the persisted `sort` column after `usort`).
        $preferences = $preferences ?? $this->defaultPreferences();
        $cards = $this->applySort(
            $cards,
            $preferences['sort_by'] ?? ProjectBoardPreference::SORT_MANUAL,
            $preferences['sort_dir'] ?? ProjectBoardPreference::DIR_ASC,
        );

        return $cards;
    }

    /**
     * @param  iterable  $notes
     * @param  iterable  $tasks
     * @param  iterable  $reports
     * @param  iterable  $todos
     * @param  iterable  $files
     * @return array<int, array<string, mixed>>
     */
    private function buildCards(Project $project, Carbon $date, $notes, $tasks, $reports, $todos, $files): array
    {
        $placements = $project->boardItems()
            ->with('category')
            ->whereDate('for_date', $date->toDateString())
            ->get()
            ->keyBy(fn (ProjectBoardItem $item) => $item->itemable_type.':'.$item->itemable_id);

        $cards = [];
        $autoIndex = 0;

        $addCard = function (string $type, int $id, string $title, array $extra = []) use (&$cards, $placements, &$autoIndex) {
            $morph = ProjectBoardItem::morphClassFor($type);
            /** @var ProjectBoardItem|null $placement */
            $placement = $placements->get("{$morph}:{$id}");

            $extra['comments_count'] = (int) ($extra['comments_count'] ?? 0);
            $extra['sort'] = (int) ($placement->sort ?? 0);
            $extra['category_id'] = $placement->category_id ?? null;
            $extra['category'] = $placement?->category ? [
                'id' => $placement->category->id,
                'slug' => $placement->category->slug,
                'name' => $placement->category->localizedName(),
                'color' => $placement->category->color,
                'text_color' => $placement->category->text_color,
            ] : null;

            $cards[] = array_merge([
                'type' => $type,
                'id' => $id,
                'title' => $title,
                'lane' => $placement->lane ?? 'backlog',
                'pos_x' => $placement->pos_x ?? 24,
                'pos_y' => $placement->pos_y ?? (24 + ($autoIndex * 12)),
            ], $extra);

            $autoIndex++;
        };

        foreach ($notes as $note) {
            $noteTitle = $note->title ?: ($note->content ? mb_strimwidth($note->content, 0, 80, '…') : __('general.sticky_note'));
            $addCard('note', $note->id, $noteTitle, [
                'color' => $note->color,
                'content' => $note->content,
                'comments_count' => $note->comments_count,
            ]);
        }

        foreach ($tasks as $task) {
            $addCard('task', $task->id, $task->task_name, [
                'description' => $task->task_description,
                'priority' => $task->priority,
                'done' => method_exists($task, 'completed') ? $task->completed() : false,
                'comments_count' => $task->comments_count,
            ]);
        }

        foreach ($reports as $report) {
            $addCard('report', $report->id, $report->title, [
                'description' => $report->body,
                'body' => $report->body,
                'published_at' => optional($report->published_at)->toIso8601String(),
                'comments_count' => $report->comments_count,
            ]);
        }

        foreach ($todos as $todo) {
            $checklist = $todo->checklistItems()->get()->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'is_completed' => (bool) $item->is_completed,
            ])->toArray();

            $addCard('todo', $todo->id, $todo->title ?: __('general.todo'), [
                'description' => $todo->description,
                'completed' => (bool) $todo->completed,
                'checklist' => $checklist,
                'comments_count' => $todo->comments_count,
            ]);
        }

        foreach ($files as $file) {
            $addCard('file', $file->id, $file->original_name ?: __('general.file'), [
                'size' => $file->size,
                'human_size' => $file->humanSize(),
                'mime' => $file->mime,
                'download_url' => route('client.projects.files.download', [$project->id, $file->id]),
                'comments_count' => $file->comments_count,
            ]);
        }

        // Cards are rendered by lane-filter on the frontend, but the persisted `sort`
        // is per-lane on the DB. So within a single visible filter (a single lane),
        // sort by `sort`. Cross-lane fallback keeps the file ordering stable.
        usort($cards, function ($a, $b) {
            if (($a['lane'] ?? null) !== ($b['lane'] ?? null)) {
                return ($a['lane'] ?? '') <=> ($b['lane'] ?? '');
            }
            $sa = (int) ($a['sort'] ?? 0);
            $sb = (int) ($b['sort'] ?? 0);

            return $sa <=> $sb ?: ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });

        return $cards;
    }
}
