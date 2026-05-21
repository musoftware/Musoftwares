<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\ServiceCategory;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::all();
        return \Inertia\Inertia::render('Admin/Marketplace/Categories', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = ServiceCategory::create($validated);

        return redirect()->back()->with('success', 'Category created.');
    }

    public function update(Request $request, ServiceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated.');
    }

    public function destroy(ServiceCategory $category)
    {
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted.');
    }
}
