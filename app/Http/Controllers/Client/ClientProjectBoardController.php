<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Project\MoveCardRequest;
use App\Http\Requests\Client\Project\StoreBoardNoteRequest;
use App\Http\Requests\Client\Project\UpdateBoardNoteRequest;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\Task;
use App\Models\Todo;
use App\Models\ProjectFile;
use App\Models\ProjectReport;
use App\Models\TodoChecklistItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientProjectBoardController extends Controller
{
    use ResolvesClientProject;

    // ───────────────── Note Actions ─────────────────

    public function storeNote(StoreBoardNoteRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $date = $data['for_date'];

        if ($this->shouldHideFuture($project, Carbon::createFromFormat('!Y-m-d', $date))) {
            abort(422, __('general.future_items_are_hidden'));
        }

        $note = $project->boardNotes()->create([
            'author_id' => $request->user()->id,
            'for_date' => $date,
            'content' => $data['content'] ?? null,
            'color' => $data['color'] ?? 'yellow',
        ]);

        $placement = $this->place($project, $date, $note, $data['lane'] ?? 'backlog', $data['pos_x'] ?? 24, $data['pos_y'] ?? 24);

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
        ]);

        $date = $data['for_date'];

        $task = $project->tasks()->create([
            'user_id' => $project->user_id, // link task to client user
            'task_name' => $data['task_name'],
            'task_description' => $data['task_description'] ?? null,
            'due_date' => $date,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, Task::class, $task->id);

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

        if (!empty($data['checklist'])) {
            foreach ($data['checklist'] as $itemTitle) {
                if (trim($itemTitle)) {
                    $todo->checklistItems()->create([
                        'title' => trim($itemTitle),
                        'is_completed' => false,
                    ]);
                }
            }
        }

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, Todo::class, $todo->id);

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
                    'is_completed' => (bool)($chk['is_completed'] ?? false),
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
        $placement = $this->place($project, $date, null, $request->input('lane', 'backlog'), 24, 24, ProjectFile::class, $file->id);

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
        ]);

        $date = $data['for_date'];

        $report = $project->reports()->create([
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? now(),
        ]);

        $placement = $this->place($project, $date, null, $data['lane'] ?? 'backlog', 24, 24, ProjectReport::class, $report->id);

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

    // ───────────────── Move Action ─────────────────

    public function moveCard(MoveCardRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $morphClass = $request->morphClass();

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
        );

        return response()->json([
            'ok' => true,
            'lane' => $placement->lane,
            'pos_x' => $placement->pos_x,
            'pos_y' => $placement->pos_y,
        ]);
    }

    public function bringUndone(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'for_date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = $data['for_date'];

        // Find all placements before the target date
        $pastPlacements = ProjectBoardItem::where('project_id', $project->id)
            ->where('for_date', '<', $targetDate)
            ->get();

        $newCards = [];

        foreach ($pastPlacements as $placement) {
            $itemable = $placement->itemable;
            if (!$itemable) {
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
                $incompleteTodos = $todos->filter(fn ($t) => !$t->completed);

                if (($todos->isNotEmpty() && $incompleteTodos->isNotEmpty()) || ($todos->isEmpty() && !$itemable->completed())) {
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
                $incompleteChecklist = $checklist->filter(fn ($chk) => !$chk->is_completed);

                if (!$itemable->completed || ($checklist->isNotEmpty() && $incompleteChecklist->isNotEmpty())) {
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
     */
    private function place(Project $project, string $date, ?ProjectBoardNote $note, string $lane, ?int $x, ?int $y, ?string $morphClass = null, ?int $morphId = null): ProjectBoardItem
    {
        $morphClass = $morphClass ?? ProjectBoardNote::class;
        $morphId = $morphId ?? $note?->id;

        return ProjectBoardItem::updateOrCreate(
            [
                'project_id' => $project->id,
                'for_date' => $date,
                'itemable_type' => $morphClass,
                'itemable_id' => $morphId,
            ],
            [
                'lane' => $lane,
                'pos_x' => $x ?? 0,
                'pos_y' => $y ?? 0,
            ],
        );
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
        return [
            'type' => 'note',
            'id' => $note->id,
            'title' => $note->content ?: __('general.sticky_note'),
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'color' => $note->color,
            'content' => $note->content,
        ];
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
            'priority' => $task->priority,
            'done' => method_exists($task, 'completed') ? $task->completed() : false,
        ];
    }

    private function todoToCard(Todo $todo, ?ProjectBoardItem $placement): array
    {
        $checklist = $todo->checklistItems()->get()->map(fn($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'is_completed' => (bool)$item->is_completed,
        ])->toArray();

        return [
            'type' => 'todo',
            'id' => $todo->id,
            'title' => $todo->title ?: __('general.todo'),
            'description' => $todo->description,
            'lane' => $placement->lane ?? 'backlog',
            'pos_x' => $placement->pos_x ?? 24,
            'pos_y' => $placement->pos_y ?? 24,
            'completed' => (bool)$todo->completed,
            'checklist' => $checklist,
        ];
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
            'size' => $file->size,
            'human_size' => $file->humanSize(),
            'mime' => $file->mime,
            'download_url' => route('client.projects.files.download', [$file->project_id, $file->id]),
        ];
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
            'published_at' => optional($report->published_at)->toIso8601String(),
        ];
    }
}
