<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PublicProjectCommentController extends Controller
{
    /**
     * GET /shared-board/{token}/comments/{type}/{id}
     * List comments for any card type, accessible to anyone with the share token.
     */
    public function index(Request $request, string $token, string $type, int $id): JsonResponse
    {
        $project = $this->resolveProject($token);
        $this->guardType($type);

        $commentable = $this->resolveCommentable($project, $type, $id);
        if (! $commentable) {
            abort(404);
        }

        $comments = $commentable->comments()
            ->with('author')
            ->latest()
            ->get()
            ->map(fn (ProjectComment $c) => $this->serialize($c))
            ->all();

        return response()->json([
            'comments' => $comments,
            'count' => count($comments),
        ]);
    }

    /**
     * POST /shared-board/{token}/comments
     * Allow guests (and logged-in visitors using the share link) to comment on any card type.
     * Authenticated users don't need to provide guest details.
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $project = $this->resolveProject($token);
        $user = $request->user();

        $rules = [
            'type' => ['required', 'string', Rule::in(ProjectBoardItem::validTypeKeys())],
            'commentable_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ];

        if (! $user) {
            $rules['guest_name'] = ['required', 'string', 'max:120'];
            $rules['guest_email'] = ['required', 'email', 'max:190'];
        } else {
            $rules['guest_name'] = ['nullable', 'string', 'max:120'];
            $rules['guest_email'] = ['nullable', 'email', 'max:190'];
        }

        $data = $request->validate($rules, [], [
            'guest_name' => __('general.guest_name'),
            'guest_email' => __('general.guest_email'),
        ]);

        $commentable = $this->resolveCommentable($project, $data['type'], (int) $data['commentable_id']);
        if (! $commentable) {
            abort(404, __('general.card_not_found'));
        }

        $comment = $commentable->comments()->create([
            'project_id' => $project->id,
            'author_id' => $user?->id,
            'guest_name' => $data['guest_name'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'body' => $data['body'],
        ]);
        $comment->load('author');

        return response()->json([
            'ok' => true,
            'comment' => $this->serialize($comment),
        ]);
    }

    private function resolveProject(string $token): Project
    {
        return Project::where('share_token', $token)->firstOrFail();
    }

    private function guardType(string $type): void
    {
        if (! in_array($type, ProjectBoardItem::validTypeKeys(), true)) {
            abort(422, __('general.invalid_card_type'));
        }
    }

    private function resolveCommentable(Project $project, string $type, int $id)
    {
        return match ($type) {
            'note' => $project->boardNotes()->whereKey($id)->first(),
            'task' => $project->tasks()->whereKey($id)->first(),
            'todo' => $project->todos()->whereKey($id)->first(),
            'report' => $project->publishedReports()->whereKey($id)->first(),
            'file' => $project->files()->whereKey($id)->first(),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ProjectComment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'author_name' => $comment->author?->name,
            'is_guest' => $comment->author_id === null,
            'guest_name' => $comment->guest_name,
            'guest_email' => $comment->guest_email,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }
}
