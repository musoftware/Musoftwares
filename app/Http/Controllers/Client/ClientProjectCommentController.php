<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Project\StoreCommentRequest;
use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Http\Request;

class ClientProjectCommentController extends Controller
{
    use ResolvesClientProject;

    public function commentsIndex(Request $request, Project $project, string $type, int $id)
    {
        $this->authorizeProject($project);

        $commentable = $this->resolveCommentable($project, $type, $id);

        return response()->json(['comments' => $this->serialize($commentable->comments()->with('author')->latest()->get())]);
    }

    public function store(StoreCommentRequest $request, Project $project)
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $commentable = $this->resolveCommentable($project, $data['type'], (int) $data['commentable_id']);

        $comment = $commentable->comments()->create([
            'project_id' => $project->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $comment->load('author');

        return response()->json(['ok' => true, 'comment' => $this->serialize(collect([$comment]))[0]]);
    }

    /**
     * Resolve a polymorphic comment target that belongs to the project and is visible to the client.
     */
    private function resolveCommentable(Project $project, string $type, int $id)
    {
        $model = match ($type) {
            'note' => $project->boardNotes()->whereKey($id)->first(),
            'task' => $project->tasks()->whereKey($id)->first(),
            'report' => $project->publishedReports()->whereKey($id)->first(),
            default => null,
        };

        abort_unless($model, 422, __('general.card_not_found'));

        return $model;
    }

    /**
     * @param  iterable<int, ProjectComment>  $comments
     * @return array<int, array<string, mixed>>
     */
    private function serialize($comments): array
    {
        return collect($comments)->map(fn (ProjectComment $comment) => [
            'id' => $comment->id,
            'body' => $comment->body,
            'author_name' => $comment->author?->name,
            'created_at' => $comment->created_at?->toIso8601String(),
        ])->all();
    }
}
