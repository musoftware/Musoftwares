<?php

namespace Modules\DigitalProducts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DigitalProducts\Models\DigitalCategory;

class AdminDigitalCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = DigitalCategory::withCount('products')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/DigitalProducts/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:digital_categories,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        DigitalCategory::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'ri-book-line',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.digitalproducts.categories.index')
            ->with('success', 'تمت إضافة التصنيف بنجاح!');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = DigitalCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:digital_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'ri-book-line',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.digitalproducts.categories.index')
            ->with('success', 'تم تعديل التصنيف بنجاح!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = DigitalCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.digitalproducts.categories.index')
            ->with('success', 'تم حذف التصنيف بنجاح.');
    }
}
