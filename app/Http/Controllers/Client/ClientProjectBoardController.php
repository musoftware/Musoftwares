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
use App\Services\AI\ProjectBoardAiService;
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
use App\Models\AdminSettings;
use Illuminate\Support\Facades\Http;

class ClientProjectBoardController extends Controller
{
    use ResolvesClientProject;

    private ?ProjectBoardService $boardService = null;

    private function boardService(): ProjectBoardService
    {
        return $this->boardService ??= app(ProjectBoardService::class);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Note Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function storeNote(StoreBoardNoteRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $this->cairoToUtc($data['published_at']);
        }
        $date = $data['for_date'];
        $isAdmin = $request->user()?->isAdmin() === true;

        if (! $isAdmin && $this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $date))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        $note = $project->boardNotes()->create([
            'author_id' => $request->user()?->id ?? $project->user_id,
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
            $data['published_at'] ?? null,
            array_key_exists('published_at', $data),
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

        $placement = $owned->boardItems()->first();
        if ($placement && array_key_exists('published_at', $data)) {
            $placement->published_at = $this->cairoToUtc($data['published_at']);
            $placement->save();
        }

        return response()->json(['ok' => true, 'card' => $this->noteToCard($owned, $placement)]);
    }

    public function destroyNote(Request $request, Project $project, ProjectBoardNote $note)
    {
        $this->authorizeProject($project);
        $owned = $project->boardNotes()->whereKey($note->id)->firstOrFail();
        $owned->delete(); // cascades to board items via model observer

        return response()->json(['ok' => true]);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Task Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            'published_at' => 'nullable|date',
        ]);

        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $this->cairoToUtc($data['published_at']);
        }

        $date = $data['for_date'];

        $task = $project->tasks()->create([
            'user_id' => $project->user_id, // link task to client user
            'task_name' => $data['task_name'],
            'task_description' => $data['task_description'] ?? null,
            'due_date' => $date,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place(
            $project,
            $date,
            null,
            $data['lane'] ?? 'backlog',
            24,
            24,
            Task::class,
            $task->id,
            $categoryId,
            $data['published_at'] ?? null,
            array_key_exists('published_at', $data),
        );

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
            'published_at' => 'nullable|date',
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

        if ($placement && array_key_exists('published_at', $data)) {
            $placement->published_at = $this->cairoToUtc($data['published_at']);
            $placement->save();
        }

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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Todo Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            'published_at' => 'nullable|date',
        ]);

        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $this->cairoToUtc($data['published_at']);
        }

        $date = $data['for_date'];

        $todo = $project->todos()->create([
            'user_id' => $request->user()?->id ?? $project->user_id,
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

        $placement = $this->place(
            $project,
            $date,
            null,
            $data['lane'] ?? 'backlog',
            24,
            24,
            Todo::class,
            $todo->id,
            $categoryId,
            $data['published_at'] ?? null,
            array_key_exists('published_at', $data),
        );

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
            'published_at' => 'nullable|date',
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

        if ($placement && array_key_exists('published_at', $data)) {
            $placement->published_at = $this->cairoToUtc($data['published_at']);
            $placement->save();
        }

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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ File Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function storeFile(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'for_date' => 'required|date_format:Y-m-d',
            'lane' => 'nullable|string',
            'published_at' => 'nullable|date',
        ]);

        $disk = config('filesystems.default');
        $upload = $request->file('file');
        $path = $upload->store("project-files/{$project->id}", $disk);

        $file = $project->files()->create([
            'uploaded_by' => $request->user()?->id ?? $project->user_id,
            'disk_path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime' => $upload->getMimeType(),
            'size' => $upload->getSize(),
        ]);

        $date = $request->input('for_date');
        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));
        $appearanceTime = $this->cairoToUtc($request->input('published_at'));
        $placement = $this->place(
            $project,
            $date,
            null,
            $request->input('lane', 'backlog'),
            24,
            24,
            ProjectFile::class,
            $file->id,
            $categoryId,
            $appearanceTime,
            $request->has('published_at'),
        );

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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Report Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        if (!empty($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }
        if (!empty($data['period_start'])) {
            $data['period_start'] = Carbon::parse($data['period_start'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }
        if (!empty($data['period_end'])) {
            $data['period_end'] = Carbon::parse($data['period_end'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }

        $date = $data['for_date'];

        $report = $project->reports()->create([
            'author_id' => $request->user()?->id ?? $project->user_id,
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? now()->toDateTimeString(),
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
        ]);

        $categoryId = $this->resolveCategoryId($project, $request->input('category_id'));

        $placement = $this->place(
            $project,
            $date,
            null,
            $data['lane'] ?? 'backlog',
            24,
            24,
            ProjectReport::class,
            $report->id,
            $categoryId,
            $data['published_at'] ?? null,
            array_key_exists('published_at', $data),
        );

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
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $data['published_at'] ? Carbon::parse($data['published_at'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }
        if (array_key_exists('period_start', $data)) {
            $data['period_start'] = $data['period_start'] ? Carbon::parse($data['period_start'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }
        if (array_key_exists('period_end', $data)) {
            $data['period_end'] = $data['period_end'] ? Carbon::parse($data['period_end'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }

        $report->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? $report->published_at,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
        ]);

        $placement = ProjectBoardItem::where('project_id', $project->id)
            ->where('itemable_type', ProjectReport::class)
            ->where('itemable_id', $report->id)
            ->first();

        if ($placement && array_key_exists('published_at', $data)) {
            $placement->published_at = $data['published_at'];
            $placement->save();
        }

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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Move Action â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

        $boardPublishedAt = null;
        $updatePublishedAt = false;
        if ($isAdmin && array_key_exists('published_at', $data)) {
            $boardPublishedAt = $data['published_at'] ? Carbon::parse($data['published_at'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
            $updatePublishedAt = true;
        }

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
            $boardPublishedAt,
            $updatePublishedAt,
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
            'published_at' => $placement->published_at?->toIso8601String(),
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

        // Guard against forged IDs â€” the card must belong to this project.
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
        mixed $boardPublishedAt = null,
        bool $updatePublishedAt = false,
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

        if ($updatePublishedAt) {
            $placement->published_at = $boardPublishedAt;
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
        $title = $note->title ?: ($note->content ? mb_strimwidth($note->content, 0, 80, 'â€¦') : __('general.sticky_note'));

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
            'published_at' => $report->published_at ? $report->published_at->copy()->setTimezone('Africa/Cairo')->toIso8601String() : null,
            'period_start' => $report->period_start ? $report->period_start->copy()->setTimezone('Africa/Cairo')->toIso8601String() : null,
            'period_end' => $report->period_end ? $report->period_end->copy()->setTimezone('Africa/Cairo')->toIso8601String() : null,
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
            'published_at' => $placement?->published_at ? $placement->published_at->copy()->setTimezone('Africa/Cairo')->toIso8601String() : null,
            'board_published_at' => $placement?->published_at ? $placement->published_at->copy()->setTimezone('Africa/Cairo')->toIso8601String() : null,
        ];
    }

    /**
     * Tenant-safe category lookup: returns the int id only if the row belongs to this project.
     * Treats `null` and "0" identically â€” null means "no category", which clears the chip.
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

    public function generateReportDraft(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        abort_unless((bool)$project->ai_enabled, 403, 'AI features are not enabled for this project. Please activate AI Project Manager first.');

        if (!$project->ensureAiIsCharged($request->user())) {
            return response()->json([
                'error' => 'Your AI Project Manager was deactivated due to insufficient wallet balance to cover the daily fee (10 EGP).',
                'insufficient' => true,
            ], 402);
        }

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $start = Carbon::parse($request->input('period_start'));
        $end = Carbon::parse($request->input('period_end'));

        // Query project activities modified/created in range
        $tasks = $project->tasks()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->get();

        $todos = $project->todos()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->get();

        $notes = $project->boardNotes()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->get();

        $files = $project->files()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        if ($tasks->isEmpty() && $todos->isEmpty() && $notes->isEmpty() && $files->isEmpty()) {
            return response()->json([
                'suggested_title' => 'Progress Report (' . $start->format('M d, Y') . ')',
                'draft' => "## Progress Summary\nNo tasks, todos, or updates were recorded on the board in the specified timeframe (" . $start->toDayDateTimeString() . " to " . $end->toDayDateTimeString() . ").",
            ]);
        }

        // Build the prompt for AI
        $prompt = "You are a professional project manager. Summarize the following activities for the project '{$project->project_name}' that occurred between {$start} and {$end} into a professional progress report.\n\nActivities:\n";

        if ($tasks->isNotEmpty()) {
            $prompt .= "\n### Tasks Updated or Created:\n";
            foreach ($tasks as $t) {
                $prompt .= "- Name: {$t->task_name} (Priority: {$t->priority})\n";
                if ($t->task_description) {
                    $prompt .= "  Description: {$t->task_description}\n";
                }
            }
        }

        if ($todos->isNotEmpty()) {
            $prompt .= "\n### Todos / Milestones:\n";
            foreach ($todos as $todo) {
                $status = $todo->completed ? 'Completed' : 'Pending';
                $prompt .= "- [{$status}] Title: {$todo->title}\n";
                if ($todo->description) {
                    $prompt .= "  Description: {$todo->description}\n";
                }
            }
        }

        if ($notes->isNotEmpty()) {
            $prompt .= "\n### Sticky Notes / Quick Updates:\n";
            foreach ($notes as $note) {
                $titleText = $note->title ? $note->title : 'Note';
                $prompt .= "- {$titleText}: {$note->body}\n";
            }
        }

        if ($files->isNotEmpty()) {
            $prompt .= "\n### Uploaded Files:\n";
            foreach ($files as $file) {
                $prompt .= "- File: {$file->original_name} (Mime: {$file->mime})\n";
            }
        }

        $prompt .= "\nGuidelines:
- Format the response professionally using markdown syntax (e.g., clear headers like ## Accomplishments, ## Status of Milestones, ## Notes, bullet points, checklists).
- Write the text in the same language as the input activities (usually Arabic or English) and use a professional, clear, corporate tone suitable for a client report.
- Do not output JSON, html tags, backticks, or any markdown code blocks (e.g. do not wrap the response in ```markdown). Output ONLY the raw markdown content for the report.";

        $defaultProvider = 'openai';
        $apiKey = AdminSettings::GetValue('openai_api_key', config('services.openai.key'));
        $model = AdminSettings::GetValue('openai_model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            return response()->json([
                'error' => "AI integration is not configured. Please set your OpenAI API key in admin settings.",
            ], 400);
        }

        try {
            if ($defaultProvider === 'openai') {
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful project manager assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ]);
                $draft = $response->json()['choices'][0]['message']['content'] ?? '';
            } else {
                $response = Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                ]);
                $draft = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }

            // Cleanup any wrapped markdown code blocks if the AI model outputted them despite instructions
            $draft = preg_replace('/^```markdown\s*/i', '', $draft);
            $draft = preg_replace('/^```\s*/i', '', $draft);
            $draft = preg_replace('/```$/', '', $draft);
            $draft = trim($draft);

            return response()->json([
                'suggested_title' => 'Progress Report (' . $start->format('M d, Y') . ')',
                'draft' => $draft,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI Generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateAiQuestions(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        abort_unless((bool)$project->ai_enabled, 403, 'AI features are not enabled for this project. Please activate AI Project Manager first.');

        if (!$project->ensureAiIsCharged($request->user())) {
            return response()->json([
                'error' => 'Your AI Project Manager was deactivated due to insufficient wallet balance to cover the daily fee (10 EGP).',
                'insufficient' => true,
            ], 402);
        }

        $data = $request->validate([
            'prompt' => 'required|string|max:10000',
        ]);

        try {
            $aiService = app(ProjectBoardAiService::class);
            $questionsData = $aiService->generatePlanQuestions($project, $data['prompt']);

            return response()->json([
                'ok' => true,
                'questions' => $questionsData['questions'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addWithAi(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        abort_unless((bool)$project->ai_enabled, 403, 'AI features are not enabled for this project. Please activate AI Project Manager first.');

        if (!$project->ensureAiIsCharged($request->user())) {
            return response()->json([
                'error' => 'Your AI Project Manager was deactivated due to insufficient wallet balance to cover the daily fee (10 EGP).',
                'insufficient' => true,
            ], 402);
        }

        $data = $request->validate([
            'prompt'          => 'required|string|max:10000',
            'start_date'      => 'required|date_format:Y-m-d',
            'allowed_types'   => 'nullable|array',
            'allowed_types.*' => 'string|in:note,task,todo,report',
            'max_daily_hours' => 'nullable|integer|min:1|max:24',
            'skip_days'       => 'nullable|array',
            'skip_days.*'     => 'integer|between:0,6',
            'answers'         => 'nullable|array', // key: question, value: answer
        ]);

        // Resolve with sensible defaults if the user didn't supply the new fields
        $allowedTypes   = !empty($data['allowed_types']) ? $data['allowed_types'] : ['note', 'task', 'todo', 'report'];
        $maxDailyHours  = (int) ($data['max_daily_hours'] ?? 8);
        $skipDays       = isset($data['skip_days']) ? array_map('intval', $data['skip_days']) : [5]; // 5 = Friday
        $answers        = $data['answers'] ?? [];

        try {
            $aiService = app(ProjectBoardAiService::class);
            $newCards = $aiService->generatePlan(
                $project,
                $data['prompt'],
                $data['start_date'],
                $request->user()?->id ?? $project->user_id,
                $allowedTypes,
                $maxDailyHours,
                $skipDays,
                $answers
            );

            return response()->json([
                'ok' => true,
                'cards' => $newCards,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateApproval(Request $request, Project $project, ProjectBoardItem $boardItem)
    {
        $this->authorizeProject($project);

        abort_unless((int)$boardItem->project_id === (int)$project->id, 403, 'Unauthorized.');

        $data = $request->validate([
            'client_approval_status' => 'required|string|in:approved,revision_requested',
            'client_feedback' => 'nullable|string|max:2000',
        ]);

        $boardItem->update([
            'client_approval_status' => $data['client_approval_status'],
            'client_feedback' => $data['client_feedback'] ?? null,
        ]);

        if ($data['client_approval_status'] === 'revision_requested' && !empty($data['client_feedback'])) {
            $type = array_search($boardItem->itemable_type, ProjectBoardItem::MORPH_MAP, true);
            if ($type !== false && $boardItem->itemable) {
                $commentData = [
                    'project_id' => $project->id,
                    'author_id' => $request->user()?->id,
                    'body' => "**Revision Requested:** " . $data['client_feedback'],
                ];
                if (!auth()->check()) {
                    $commentData['guest_name'] = 'Client (Guest)';
                }
                $boardItem->itemable->comments()->create($commentData);
            }
        }

        return response()->json([
            'ok' => true,
            'client_approval_status' => $boardItem->client_approval_status,
            'client_feedback' => $boardItem->client_feedback,
        ]);
    }

    private function cairoToUtc(?string $datetime): ?string
    {
        return $datetime ? Carbon::parse($datetime, 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
    }
}
