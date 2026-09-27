<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPromptController extends Controller
{
    /**
     * Enforce strict admin access defensively.
     */
    protected function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, __('general.unauthorized_access') ?: 'Unauthorized access');
        }
    }

    /**
     * Display a listing of prompts.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $this->ensureAdmin();

        $search = $request->input('search');
        $selectedCategory = $request->input('category');
        $sortBy = $request->input('sort', 'latest');

        $query = Prompt::query()
            ->with(['user:id,name,email'])
            ->search($search)
            ->category($selectedCategory);

        match ($sortBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'title' => $query->orderBy('title', 'asc'),
            'popular' => $query->orderByDesc('copy_count')->orderByDesc('created_at'),
            default => $query->orderByDesc('is_featured')->orderByDesc('created_at'),
        };

        $prompts = $query->paginate(24)->withQueryString();

        // Calculate category distribution for fast filtering chips
        $categoryCounts = Prompt::query()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // High-level statistics
        $stats = [
            'total_prompts' => Prompt::count(),
            'total_copies' => (int) Prompt::sum('copy_count'),
            'featured_count' => Prompt::where('is_featured', true)->count(),
            'categories_count' => count($categoryCounts),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'prompts' => $prompts,
                'categories' => $categoryCounts,
                'stats' => $stats,
            ]);
        }

        return Inertia::render('Admin/Prompts/Index', [
            'prompts' => $prompts,
            'categoryCounts' => $categoryCounts,
            'filters' => [
                'search' => $search ?? '',
                'category' => $selectedCategory ?? 'all',
                'sort' => $sortBy,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created prompt in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'min:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $prompt = Prompt::create([
            'title' => trim($validated['title']),
            'category' => trim($validated['category']),
            'prompt' => trim($validated['prompt']),
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'tags' => $validated['tags'] ?? [],
            'user_id' => auth()->id(),
            'copy_count' => 0,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt created successfully.',
                'prompt' => $prompt,
            ], 201);
        }

        return redirect()->back()->with('success', 'Prompt created successfully.');
    }

    /**
     * Update the specified prompt in storage.
     */
    public function update(Request $request, Prompt $prompt): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'prompt' => ['required', 'string', 'min:5'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $prompt->update([
            'title' => trim($validated['title']),
            'category' => trim($validated['category']),
            'prompt' => trim($validated['prompt']),
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'tags' => $validated['tags'] ?? [],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt updated successfully.',
                'prompt' => $prompt,
            ]);
        }

        return redirect()->back()->with('success', 'Prompt updated successfully.');
    }

    /**
     * Remove the specified prompt from storage.
     */
    public function destroy(Request $request, Prompt $prompt): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin();

        $prompt->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Prompt deleted successfully.');
    }

    /**
     * Increment the copy count when a user copies the prompt to clipboard.
     */
    public function copy(Request $request, Prompt $prompt): JsonResponse
    {
        $this->ensureAdmin();

        $prompt->increment('copy_count');

        return response()->json([
            'success' => true,
            'copy_count' => $prompt->copy_count,
            'message' => 'Prompt copied to clipboard.',
        ]);
    }
}
