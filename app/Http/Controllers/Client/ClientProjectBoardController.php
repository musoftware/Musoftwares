<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Project\MoveCardRequest;
use App\Http\Requests\Client\Project\ReorderCardsRequest;
use App\Http\Requests\Client\Project\RescheduleCardRequest;
use App\Http\Requests\Client\Project\StoreBoardNoteRequest;
use App\Http\Requests\Client\Project\UpdateBoardNoteRequest;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\ProjectFile;
use App\Models\ProjectReport;
use App\Models\Task;
use App\Models\Todo;
use App\Services\ProjectBoardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientProjectBoardController extends Controller
{
    use ResolvesClientProject;

    private ?ProjectBoardService $boardService = null;

    private function boardService(): ProjectBoardService
    {
        return $this->boardService ??= app(ProjectBoardService::class);
    }

    // ───────────────── Note Actions ─────────────────

    public function storeNote(StoreBoardNoteRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $date = $data['for_date'];
        $isAdmin = $request->user()?->isAdmin() === true;

        if (! $isAdmin && $this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $date))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        $note = $project->boardNotes()->create([
            'author_id' => $request->user()->id,
            'for_date' => $date,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'color' => $data['color'] ?? 'yellow',
        ]);

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place(
            $project,
            $date,
            $note,
            $data['lane'] ?? 'backlog',
            $data['pos_x'] ?? 24,
            $data['pos_y'] ?? 24,
            null,
            null,
            $categoryId,
        );

        return response()->json([
            'ok' => true,
            'card' => $this->noteToCard($note, $placement),
        ]);
    }

    public function updateNote(UpdateBoardNoteRequest $request, Project $project, ProjectBoardNote $note)
    {
        $this->authorizeProject($project);
        $owned = $project->boardNotes()->whereKey($note->id)->firstOrFail();

        $data = $request->validated();
        if (array_key_exists('title', $data)) {
            $owned->title = $data['title'];
        }
        if (array_key_exists('content', $data)) {
            $owned->content = $data['content'];
        }
        if (array_key_exists('color', $data)) {
            $owned->color = $data['color'];
        }
        $owned->save();

        return response()->json(['ok' => true, 'card' => $this->noteToCard($owned, $owned->boardItems()->first())]);
    }

    public function destroyNote(Request $request, Project $project, ProjectBoardNote $note)
    {
        $this->authorizeProject($project);
        $owned = $project->boardNotes()->whereKey($note->id)->firstOrFail();
        $owned->delete(); // cascades to board items via model observer

        return response()->json(['ok' => true]);
    }

    // ───────────────── Task Actions ─────────────────

    public function storeTask(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'for_date' => 'required|date_format:Y-m-d',
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'lane' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:project_board_categories,id',
        ]);

        $date = $data['for_date'];

        $task = $project->tasks()->create([
            'user_id' => $project->user_id, // link task to client user
            'task_name' => $data['task_name'],
            'task_description' => $data['task_description'] ?? null,
            'due_date' => $date,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, Task::class, $task->id, $categoryId);

        return response()->json([
            'ok' => true,
            'card' => $this->taskToCard($task, $placement),
        ]);
    }

    public function updateTask(Request $request, Project $project, Task $task)
    {
        $this->authorizeProject($project);
        abort_unless($task->project_id === $project->id, 404);

        $data = $request->validate([
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        $task->update([
            'task_name' => $data['task_name'],
            'task_description' => $data['task_description'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        $placement = ProjectBoardItem::where('project_id', $project->id)
            ->where('itemable_type', Task::class)
            ->where('itemable_id', $task->id)
            ->first();

        return response()->json([
            'ok' => true,
            'card' => $this->taskToCard($task, $placement),
        ]);
    }

    public function destroyTask(Request $request, Project $project, Task $task)
    {
        $this->authorizeProject($project);
        abort_unless($task->project_id === $project->id, 404);

        $task->delete();

        return response()->json(['ok' => true]);
    }

    // ───────────────── Todo Actions ─────────────────

    public function storeTodo(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'for_date' => 'required|date_format:Y-m-d',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'checklist' => 'nullable|array',
            'lane' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:project_board_categories,id',
        ]);

        $date = $data['for_date'];

        $todo = $project->todos()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'completed' => false,
            'inDate' => $date,
            'priority' => $data['priority'] ?? 'normal',
            'priorityColor' => $data['priorityColor'] ?? 'gray',
            'tags' => $data['tags'] ?? '[]',
            'paused' => false,
        ]);

        if (! empty($data['checklist'])) {
            foreach ($data['checklist'] as $itemTitle) {
                if (trim($itemTitle)) {
                    $todo->checklistItems()->create([
                        'title' => trim($itemTitle),
                        'is_completed' => false,
                    ]);
                }
            }
        }

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, Todo::class, $todo->id, $categoryId);

        return response()->json([
            'ok' => true,
            'card' => $this->todoToCard($todo, $placement),
        ]);
    }

    public function updateTodo(Request $request, Project $project, Todo $todo)
    {
        $this->authorizeProject($project);
        abort_unless($todo->project_id === $project->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'checklist' => 'nullable|array',
        ]);

        $todo->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'completed' => $data['completed'] ?? $todo->completed,
        ]);

        if (array_key_exists('checklist', $data)) {
            // Rebuild checklist items simply
            $todo->checklistItems()->delete();
            foreach ($data['checklist'] as $chk) {
                $todo->checklistItems()->create([
                    'title' => $chk['title'],
                    'is_completed' => (bool) ($chk['is_completed'] ?? false),
                ]);
            }
        }

        $placement = ProjectBoardItem::where('project_id', $project->id)
            ->where('itemable_type', Todo::class)
            ->where('itemable_id', $todo->id)
            ->first();

        return response()->json([
            'ok' => true,
            'card' => $this->todoToCard($todo, $placement),
        ]);
    }

    public function destroyTodo(Request $request, Project $project, Todo $todo)
    {
        $this->authorizeProject($project);
        abort_unless($todo->project_id === $project->id, 404);

        $todo->checklistItems()->delete();
        $todo->delete();

        return response()->json(['ok' => true]);
    }

    // ───────────────── File Actions ─────────────────

    public function storeFile(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'for_date' => 'required|date_format:Y-m-d',
            'lane' => 'nullable|string',
        ]);

        $disk = config('filesystems.default');
        $upload = $request->file('file');
        $path = $upload->store("project-files/{$project->id}", $disk);

        $file = $project->files()->create([
            'uploaded_by' => $request->user()->id,
            'disk_path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime' => $upload->getMimeType(),
            'size' => $upload->getSize(),
        ]);

        $date = $request->input('for_date');
        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));
        $placement = $this->place($project, $date, null, $request->input('lane', 'backlog'), 24, 24, ProjectFile::class, $file->id, $categoryId);

        return response()->json([
            'ok' => true,
            'card' => $this->fileToCard($file, $placement),
        ]);
    }

    public function destroyFile(Request $request, Project $project, ProjectFile $file)
    {
        $this->authorizeProject($project);
        abort_unless($file->project_id === $project->id, 404);

        $disk = config('filesystems.default');
        if (Storage::disk($disk)->exists($file->disk_path)) {
            Storage::disk($disk)->delete($file->disk_path);
        }
        $file->delete();

        return response()->json(['ok' => true]);
    }

    // ───────────────── Report Actions ─────────────────

    public function storeReport(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'for_date' => 'required|date_format:Y-m-d',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published_at' => 'nullable|date',
            'lane' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:project_board_categories,id',
        ]);

        $date = $data['for_date'];

        $report = $project->reports()->create([
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? now(),
        ]);

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, ProjectReport::class, $report->id, $categoryId);

        return response()->json([
            'ok' => true,
            'card' => $this->reportToCard($report, $placement),
        ]);
    }

    public function updateReport(Request $request, Project $project, ProjectReport $report)
    {
        $this->authorizeProject($project);
        abort_unless($report->project_id === $project->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        $report->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? $report->published_at,
        ]);

        $placement = ProjectBoardItem::where('project_id', $project->id)
            ->where('itemable_type', ProjectReport::class)
            ->where('itemable_id', $report->id)
            ->first();

        return response()->json([
            'ok' => true,
            'card' => $this->reportToCard($report, $placement),
        ]);
    }

    public function destroyReport(Request $request, Project $project, ProjectReport $report)
    {
        $this->authorizeProject($project);
        abort_unless($report->project_id === $project->id, 404);

        $report->delete();

        return response()->json(['ok' => true]);
    }

    public function exportReportPdf(Request $request, Project $project, ProjectReport $report)
    {
        $this->authorizeProject($project);
        abort_unless($report->project_id === $project->id, 404);

        $report->loadMissing('author', 'project');

        $pdf = Pdf::loadView('client.projects.reports.pdf', [
            'project' => $project,
            'report' => $report,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            '%s-report-%d.pdf',
            Str::slug($project->project_name ?: 'project') ?: 'project',
            $report->id,
        );

        return $pdf->download($filename);
    }

    // ───────────────── Move Action ─────────────────

    public function moveCard(MoveCardRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $morphClass = $request->morphClass();
        $isAdmin = $request->user()?->isAdmin() === true;

        // Admins may reschedule a card to any date (past, today, or future).
        // Clients are restricted by the per-project hide-future flag.
        if (! $isAdmin && $this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $data['for_date']))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        // The referenced card must belong to this project (guard against forged IDs).
        $this->resolveOwnedItemable($project, $data['type'], (int) $data['id']);

        $placement = $this->place(
            $project,
            $data['for_date'],
            null,
            $data['lane'] ?? 'backlog',
            $data['pos_x'] ?? null,
            $data['pos_y'] ?? null,
            $morphClass,
            (int) $data['id'],
            $this->resolveCategoryId($project, array_key_exists('category_id', $data) ? $data['category_id'] : null),
        );

        // Optional explicit sort: re-spaces the card inside the target lane. Done
        // separately from place() so we can keep an isolated transaction and only
        // touch the sort column.
        if (array_key_exists('sort', $data) && $data['sort'] !== null) {
            $this->respaceInLane($project, $placement, $data['lane'] ?? $placement->lane, (int) $data['sort']);
            $placement->refresh();
        }

        return response()->json([
            'ok' => true,
            'lane' => $placement->lane,
            'pos_x' => $placement->pos_x,
            'pos_y' => $placement->pos_y,
            'sort' => (int) $placement->sort,
            'category_id' => $placement->category_id,
        ]);
    }

    /**
     * Bulk reorder endpoint used after a drag-drop interaction. Accepts the new
     * order for a single (project, date, lane) and rewrites the `sort` column so
     * it matches the submitted list.
     */
    public function reorderCards(ReorderCardsRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $isAdmin = $request->user()?->isAdmin() === true;
        $date = $data['for_date'];
        $lane = $data['lane'];

        if (! $isAdmin && $this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $date))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        // Defensive: the IDs that are unknown or not owned by this project are silently skipped.
        // The frontend only sends cards it actually sees, so this is just a guardrail.
        $items = [];
        foreach ($data['order'] as $entry) {
            $type = $entry['type'];
            $id = (int) $entry['id'];
            if (! $this->ownedItemableExists($project, $type, $id)) {
                continue;
            }
            $items[] = [
                'type' => $type,
                'id' => $id,
                'morph' => ProjectBoardItem::morphClassFor($type),
            ];
        }

        DB::transaction(function () use ($project, $date, $lane, $items) {
            foreach ($items as $idx => $item) {
                // updateOrCreate keeps pos_x/pos_y intact for moves within the same lane.
                $placement = $this->place(
                    $project,
                    $date,
                    null,
                    $lane,
                    null,
                    null,
                    $item['morph'],
                    $item['id'],
                );
                $placement->sort = $idx;
                $placement->save();
            }

            // Compact sort numbers so adjacent lanes don't drift far apart across many reorders.
            $max = ProjectBoardItem::where('project_id', $project->id)
                ->where('for_date', $date)
                ->where('lane', $lane)
                ->max('sort');
            if ($max !== null) {
                ProjectBoardItem::where('project_id', $project->id)
                    ->where('for_date', $date)
                    ->where('lane', $lane)
                    ->where('sort', '>', $max)
                    ->delete(); // delete residue from prior renumberings (no-op in practice)
            }
        });

        return response()->json([
            'ok' => true,
            'lane' => $lane,
            'count' => count($items),
        ]);
    }

    /** Cheap boolean lookup used by both moveCard and reorderCards authorization checks. */
    private function ownedItemableExists(Project $project, string $type, int $id): bool
    {
        return match ($type) {
            'note' => $project->boardNotes()->whereKey($id)->exists(),
            'task' => $project->tasks()->whereKey($id)->exists(),
            'todo' => $project->todos()->whereKey($id)->exists(),
            'file' => $project->files()->whereKey($id)->exists(),
            'report' => $project->reports()->whereKey($id)->exists(),
            default => false,
        };
    }

    /**
     * Insert the placement at `$targetSort` within its lane, shifting siblings down.
     * Used by single-card `move-card` with explicit sort.
     */
    private function respaceInLane(Project $project, ProjectBoardItem $placement, string $lane, int $targetSort): void
    {
        DB::transaction(function () use ($project, $placement, $lane, $targetSort) {
            $currentSort = (int) $placement->sort;

            $siblings = ProjectBoardItem::where('project_id', $project->id)
                ->where('for_date', $placement->for_date->toDateString())
                ->where('lane', $lane)
                ->where('id', '!=', $placement->id)
                ->orderBy('sort')
                ->lockForUpdate()
                ->get();

            $ordered = $siblings->pluck('id')->all();
            array_splice($ordered, max(0, min(count($ordered), $targetSort)), 0, [$placement->id]);

            foreach ($ordered as $i => $id) {
                ProjectBoardItem::where('id', $id)->update(['sort' => $i]);
            }
        });
    }

    public function rescheduleCard(RescheduleCardRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $type = $data['type'];
        $id = (int) $data['id'];
        $newDate = $data['for_date'];

        // Guard against forged IDs — the card must belong to this project.
        $this->resolveOwnedItemable($project, $type, $id);

        $morphClass = ProjectBoardItem::morphClassFor($type);

        // Files have no separate board-date column (their `created_at` is the
        // board day), so rescheduling a file is intentionally not supported.
        // The Form Request restricts rescheduling to the four itemables that
        // own an explicit date column.
        if ($type === 'file') {
            abort(422, __('general.card_reschedule_not_supported'));
        }

        DB::transaction(function () use ($project, $type, $morphClass, $id, $newDate) {
            match ($type) {
                'note' => $project->boardNotes()->whereKey($id)->update(['for_date' => $newDate]),
                'task' => $project->tasks()->whereKey($id)->update(['due_date' => $newDate]),
                'todo' => $project->todos()->whereKey($id)->update(['inDate' => $newDate]),
                'report' => $project->reports()->whereKey($id)->update(['published_at' => $newDate]),
                default => null,
            };

            // Move every saved placement row for this card to the new date so
            // the saved lane / pos_x / pos_y layout survives the reschedule.
            ProjectBoardItem::where('project_id', $project->id)
                ->where('itemable_type', $morphClass)
                ->where('itemable_id', $id)
                ->update(['for_date' => $newDate]);
        });

        return response()->json([
            'ok' => true,
            'for_date' => $newDate,
        ]);
    }

    public function bringUndone(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'for_date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = $data['for_date'];
        $isAdmin = $request->user()?->isAdmin() === true;

        // Admins may bring undone items onto any date; clients cannot target future dates
        // when the per-project hide-future flag is enabled.
        if (! $isAdmin && $this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $targetDate))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        // Find all placements before the target date
        $pastPlacements = ProjectBoardItem::where('project_id', $project->id)
            ->where('for_date', '<', $targetDate)
            ->get();

        $newCards = [];

        foreach ($pastPlacements as $placement) {
            $itemable = $placement->itemable;
            if (! $itemable) {
                continue;
            }

            // Handle Task
            if ($placement->itemable_type === Task::class) {
                // Prevent duplicate copy if it already exists today
                $alreadyCopied = ProjectBoardItem::where('project_id', $project->id)
                    ->where('for_date', $targetDate)
                    ->where('itemable_type', Task::class)
                    ->whereHas('itemable', function ($q) use ($itemable) {
                        $q->where('task_name', $itemable->task_name);
                    })->exists();

                if ($alreadyCopied) {
                    continue;
                }

                $todos = $itemable->task_todo_items()->get();
                $incompleteTodos = $todos->filter(fn ($t) => ! $t->completed);

                if (($todos->isNotEmpty() && $incompleteTodos->isNotEmpty()) || ($todos->isEmpty() && ! $itemable->completed())) {
                    $newTask = $project->tasks()->create([
                        'user_id' => $itemable->user_id,
                        'task_name' => $itemable->task_name,
                        'task_description' => $itemable->task_description,
                        'due_date' => $targetDate,
                        'priority' => $itemable->priority,
                    ]);

                    foreach ($incompleteTodos as $t) {
                        $newTodo = $newTask->task_todo_items()->create([
                            'project_id' => $project->id,
                            'user_id' => $t->user_id,
                            'title' => $t->title,
                            'description' => $t->description,
                            'completed' => false,
                            'inDate' => $targetDate,
                            'priority' => $t->priority,
                        ]);

                        foreach ($t->checklistItems()->where('is_completed', false)->get() as $chk) {
                            $newTodo->checklistItems()->create([
                                'title' => $chk->title,
                                'is_completed' => false,
                            ]);
                        }
                    }

                    $newPlacement = $this->place($project, $targetDate, null, 'backlog', 24, 24, Task::class, $newTask->id);
                    $newCards[] = $this->taskToCard($newTask, $newPlacement);
                }
            }

            // Handle Todo
            if ($placement->itemable_type === Todo::class) {
                if ($itemable->task_id !== null) {
                    continue;
                }

                $alreadyCopied = ProjectBoardItem::where('project_id', $project->id)
                    ->where('for_date', $targetDate)
                    ->where('itemable_type', Todo::class)
                    ->whereHas('itemable', function ($q) use ($itemable) {
                        $q->where('title', $itemable->title);
                    })->exists();

                if ($alreadyCopied) {
                    continue;
                }

                $checklist = $itemable->checklistItems()->get();
                $incompleteChecklist = $checklist->filter(fn ($chk) => ! $chk->is_completed);

                if (! $itemable->completed || ($checklist->isNotEmpty() && $incompleteChecklist->isNotEmpty())) {
                    $newTodo = $project->todos()->create([
                        'user_id' => $itemable->user_id,
                        'title' => $itemable->title,
                        'description' => $itemable->description,
                        'completed' => false,
                        'inDate' => $targetDate,
                        'priority' => $itemable->priority ?: 'normal',
                        'priorityColor' => $itemable->priorityColor ?: 'gray',
                        'tags' => $itemable->tags ?: '[]',
                        'paused' => false,
                    ]);

                    foreach ($incompleteChecklist as $chk) {
                        $newTodo->checklistItems()->create([
                            'title' => $chk->title,
                            'is_completed' => false,
                        ]);
                    }

                    $newPlacement = $this->place($project, $targetDate, null, 'backlog', 24, 24, Todo::class, $newTodo->id);
                    $newCards[] = $this->todoToCard($newTodo, $newPlacement);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'new_cards' => $newCards,
        ]);
    }

    /**
     * Update-or-create the polymorphic placement row for a card on a given day board.
     *
     * When category_id is null the previously-saved category is preserved (updateOrCreate only touches
     * the columns listed in the second array, so omitting the key intentionally keeps existing data).
     * `sort` defaults to "put me at the end of the lane I just landed in" so newly-created cards
     * appear after existing ones without any extra coordination from the caller.
     */
    private function place(
        Project $project,
        string $date,
        ?ProjectBoardNote $note,
        string $lane,
        ?int $x,
        ?int $y,
        ?string $morphClass = null,
        ?int $morphId = null,
        ?int $categoryId = null,
    ): ProjectBoardItem {
        $morphClass = $morphClass ?? ProjectBoardNote::class;
        $morphId = $morphId ?? $note?->id;

        $attributes = [
            'project_id' => $project->id,
            'for_date' => $date,
            'itemable_type' => $morphClass,
            'itemable_id' => $morphId,
        ];

        $values = [
            'lane' => $lane,
            'pos_x' => $x ?? 0,
            'pos_y' => $y ?? 0,
        ];

        // Only assign a sort value on insert. On update we never want to bump the sort
        // because updateOrCreate would clobber persisted drag-drop orderings.
        $existing = ProjectBoardItem::where($attributes)->first();
        if (! $existing) {
            $nextSort = (int) (ProjectBoardItem::where('project_id', $project->id)
                ->where('for_date', $date)
                ->where('lane', $lane)
                ->max('sort') ?? -1) + 1;
            $values['sort'] = $nextSort;
        }

        $placement = ProjectBoardItem::updateOrCreate($attributes, $values);

        // When the caller explicitly supplies category_id (incl. null = "clear"), honor it.
        // Omitting the parameter entirely leaves the category untouched on existing rows.
        if (func_num_args() >= 9) {
            $placement->category_id = $categoryId;
            $placement->save();
        }

        return $placement;
    }

    private function resolveOwnedItemable(Project $project, string $type, int $id): void
    {
        $exists = match ($type) {
            'note' => $project->boardNotes()->whereKey($id)->exists(),
            'task' => $project->tasks()->whereKey($id)->exists(),
            'todo' => $project->todos()->whereKey($id)->exists(),
            'file' => $project->files()->whereKey($id)->exists(),
            // Clients may only place reports they can actually see (published + scheduled time reached).
            'report' => $project->reports()->whereKey($id)->exists(),
            default => false,
        };

        abort_unless($exists, 422, __('general.card_not_found'));
    }

    private function noteToCard(ProjectBoardNote $note, ?ProjectBoardItem $placement): array
    {
        $title = $note->title ?: ($note->content ? mb_strimwidth($note->content, 0, 80, '…') : __('general.sticky_note'));

        return [
            'type' => 'note',
            'id' => $note->id,
            'title' => $title,
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'sort' => (int) ($placement->sort ?? 0),
            'color' => $note->color,
            'content' => $note->content,
            'comments_count' => (int) $note->comments()->count(),
        ] + $this->categoryPayload($placement);
    }

    private function taskToCard(Task $task, ?ProjectBoardItem $placement): array
    {
        return [
            'type' => 'task',
            'id' => $task->id,
            'title' => $task->task_name,
            'description' => $task->task_description,
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'sort' => (int) ($placement->sort ?? 0),
            'priority' => $task->priority,
            'done' => method_exists($task, 'completed') ? $task->completed() : false,
            'comments_count' => (int) $task->comments()->count(),
        ] + $this->categoryPayload($placement);
    }

    private function todoToCard(Todo $todo, ?ProjectBoardItem $placement): array
    {
        $checklist = $todo->checklistItems()->get()->map(fn ($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'is_completed' => (bool) $item->is_completed,
        ])->toArray();

        return [
            'type' => 'todo',
            'id' => $todo->id,
            'title' => $todo->title ?: __('general.todo'),
            'description' => $todo->description,
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'sort' => (int) ($placement->sort ?? 0),
            'completed' => (bool) $todo->completed,
            'checklist' => $checklist,
            'comments_count' => (int) $todo->comments()->count(),
        ] + $this->categoryPayload($placement);
    }

    private function fileToCard(ProjectFile $file, ?ProjectBoardItem $placement): array
    {
        return [
            'type' => 'file',
            'id' => $file->id,
            'title' => $file->original_name ?: __('general.file'),
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'sort' => (int) ($placement->sort ?? 0),
            'size' => $file->size,
            'human_size' => $file->humanSize(),
            'mime' => $file->mime,
            'download_url' => route('client.projects.files.download', [$file->project_id, $file->id]),
            'comments_count' => (int) $file->comments()->count(),
        ] + $this->categoryPayload($placement);
    }

    private function reportToCard(ProjectReport $report, ?ProjectBoardItem $placement): array
    {
        return [
            'type' => 'report',
            'id' => $report->id,
            'title' => $report->title,
            'description' => $report->body,
            'body' => $report->body,
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'sort' => (int) ($placement->sort ?? 0),
            'published_at' => optional($report->published_at)->toIso8601String(),
            'comments_count' => (int) $report->comments()->count(),
        ] + $this->categoryPayload($placement);
    }

    /**
     * Convert the placement's category relation into the trimmed payload the UI needs
     * (id-only when no category, full chip info otherwise).
     *
     * @return array<string, mixed>
     */
    private function categoryPayload(?ProjectBoardItem $placement): array
    {
        $category = $placement?->category;

        return [
            'category_id' => $placement?->category_id,
            'category' => $category ? [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->localizedName(),
                'color' => $category->color,
                'text_color' => $category->text_color,
            ] : null,
        ];
    }

    /**
     * Tenant-safe category lookup: returns the int id only if the row belongs to this project.
     * Treats `null` and "0" identically — null means "no category", which clears the chip.
     */
    private function resolveCategoryId(Project $project, mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === 0 || $raw === '0') {
            return null;
        }

        $id = (int) $raw;
        if ($id <= 0) {
            return null;
        }

        $exists = $project->boardCategories()->whereKey($id)->exists();
        abort_unless($exists, 422, __('general.invalid_board_category'));

        return $id;
    }
}
