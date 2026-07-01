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
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientProjectBoardController extends Controller
{
    use ResolvesClientProject;

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
                'pos_x' => $x,
                'pos_y' => $y,
            ],
        );
    }

    private function resolveOwnedItemable(Project $project, string $type, int $id): void
    {
        $exists = match ($type) {
            'note' => $project->boardNotes()->whereKey($id)->exists(),
            'task' => $project->tasks()->whereKey($id)->exists(),
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
}
