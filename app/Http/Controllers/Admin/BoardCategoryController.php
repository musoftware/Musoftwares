<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoardCategory;
use App\Services\ProjectBoardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin-only management for a project's board categories. Clients cannot create custom categories —
 * they see (and may assign) the project's full category list via the board UI but the CRUD endpoints
 * are gated behind `admin` middleware at the route level.
 */
class BoardCategoryController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $service = app(ProjectBoardService::class);

        return response()->json([
            'ok' => true,
            'categories' => $service->categoriesFor($project)->map(fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'name_ar' => $c->name_ar,
                'color' => $c->color,
                'text_color' => $c->text_color,
                'is_system' => (bool) $c->is_system,
                'sort' => (int) $c->sort,
            ])->values(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_ar' => ['nullable', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:30'],
            'text_color' => ['nullable', 'string', 'max:30'],
        ]);

        // Slug must be unique within the project so URL params and react keys stay stable.
        $baseSlug = Str::slug($data['name']) ?: 'category';
        $slug = $baseSlug;
        $i = 1;
        while ($project->boardCategories()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i++;
        }

        $maxSort = (int) ($project->boardCategories()->max('sort') ?? 0);

        $category = $project->boardCategories()->create([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'slug' => $slug,
            'color' => $data['color'],
            'text_color' => $data['text_color'] ?? $data['color'],
            'is_system' => false,
            'sort' => $maxSort + 10,
        ]);

        return response()->json(['ok' => true, 'category' => $this->serialize($category)]);
    }

    public function update(Request $request, Project $project, ProjectBoardCategory $category)
    {
        $this->authorize('view', $project);
        abort_unless($category->project_id === $project->id, 404);

        // System categories can't be deleted or have their slug retargeted (clients
        // depend on the slug for stable analytics and seed URLs).
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_ar' => ['nullable', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:30'],
            'text_color' => ['nullable', 'string', 'max:30'],
        ]);

        $category->update([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'color' => $data['color'],
            'text_color' => $data['text_color'] ?? $data['color'],
        ]);

        return response()->json(['ok' => true, 'category' => $this->serialize($category)]);
    }

    public function destroy(Request $request, Project $project, ProjectBoardCategory $category)
    {
        $this->authorize('view', $project);
        abort_unless($category->project_id === $project->id, 404);

        if ($category->is_system) {
            // Don't delete the seeded system categories — they're shared semantics
            // across every project. Reset to "no category" instead.
            DB::table('project_board_items')
                ->where('category_id', $category->id)
                ->update(['category_id' => null]);

            return response()->json(['ok' => true, 'cleared' => true]);
        }

        // FK is `nullOnDelete` so placements automatically drop their reference.
        $category->delete();

        return response()->json(['ok' => true, 'deleted' => true]);
    }

    private function serialize(ProjectBoardCategory $category): array
    {
        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'name_ar' => $category->name_ar,
            'color' => $category->color,
            'text_color' => $category->text_color,
            'is_system' => (bool) $category->is_system,
            'sort' => (int) $category->sort,
        ];
    }
}
