<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Project\StoreCommentRequest;
use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\ProjectBoardItem;
use App\Services\AI\ProjectBoardAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientProjectCommentController extends Controller
{
    use ResolvesClientProject;

    public function commentsIndex(Request $request, Project $project, string $type, int $id): JsonResponse
    {
        $this->authorizeProject($project);

        $commentable = $this->resolveCommentable($project, $type, $id);

        return response()->json(['comments' => $this->serialize($commentable->comments()->with('author')->latest()->get())]);
    }

    public function store(StoreCommentRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validated();
        $commentable = $this->resolveCommentable($project, $data['type'], (int) $data['commentable_id']);

        $body = $data['body'];

        if ($request->hasFile('file')) {
            $upload = $request->file('file');
            $disk = config('filesystems.default');
            $path = $upload->store("project-files/{$project->id}", $disk);

            $projectFile = $project->files()->create([
                'uploaded_by' => $request->user()?->id ?? $project->user_id,
                'disk_path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime' => $upload->getMimeType(),
                'size' => $upload->getSize(),
            ]);

            $downloadUrl = route('client.projects.files.download', ['project' => $project->id, 'file' => $projectFile->id]);
            $body = trim($body . "\n\n📄 **Attachment**: [{$projectFile->original_name}]({$downloadUrl})");
        }

        $comment = $commentable->comments()->create([
            'project_id' => $project->id,
            'author_id' => $request->user()?->id,
            'body' => $body,
            'parent_id' => $data['parent_id'] ?? null,
            'commentable_type' => $data['type'] === 'project' ? \App\Models\Project::class : $commentable->getMorphClass(),
            'commentable_id' => $data['type'] === 'project' ? $project->id : $commentable->id,
        ]);
        $comment->load('author');

        $aiAdjusted = false;

        if ($project->ai_enabled && $data['type'] === 'project' && $request->user()?->id) {
            \App\Jobs\ProcessClientMessageWithAi::dispatch($project, $comment);
        }

        if (!empty($data['adjust_future_ai']) && $data['type'] !== 'project') {
            $morphClass = ProjectBoardItem::morphClassFor($data['type']);
            $boardItem = ProjectBoardItem::where('project_id', $project->id)
                ->where('itemable_type', $morphClass)
                ->where('itemable_id', $data['commentable_id'])
                ->first();

            if ($boardItem && $boardItem->is_ai) {
                try {
                    $aiService = app(ProjectBoardAiService::class);
                    $aiService->adjustFutureItems(
                        $project,
                        $data['body'],
                        (int)$data['commentable_id'],
                        $morphClass,
                        $request->user()?->id ?? $project->user_id
                    );
                    $aiAdjusted = true;
                } catch (\Exception $e) {
                    Log::error('AI future adjustment failed: ' . $e->getMessage());
                    if (app()->runningUnitTests()) {
                        throw $e;
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'comment' => $this->serialize(collect([$comment]))[0],
            'ai_adjusted' => $aiAdjusted,
        ]);
    }

    /**
     * Resolve a polymorphic comment target that belongs to the project and is visible to the client.
     */
    private function resolveCommentable(Project $project, string $type, int $id)
    {
        $model = match ($type) {
            'project' => $project->id === $id ? $project : null,
            'note' => $project->boardNotes()->whereKey($id)->first(),
            'task' => $project->tasks()->whereKey($id)->first(),
            'todo' => $project->todos()->whereKey($id)->first(),
            'report' => $project->publishedReports()->whereKey($id)->first(),
            'file' => $project->files()->whereKey($id)->first(),
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
            'is_guest' => $comment->author_id === null,
            'guest_name' => $comment->guest_name,
            'guest_email' => $comment->guest_email,
            'created_at' => $comment->created_at?->toIso8601String(),
            'parent_id' => $comment->parent_id,
        ])->all();
    }
}
