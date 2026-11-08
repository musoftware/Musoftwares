<?php

namespace Modules\Shop\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shop\Models\ShopCategory;
use Modules\Shop\Support\UniqueSlug;

class AdminShopCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ShopCategory::withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ShopCategory $category) => $category->toArray() + ['image_url' => $category->imageUrl(), 'url' => $category->url()]);

        return Inertia::render('Admin/Shop/Categories', ['categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new ShopCategory);

        return back();
    }

    public function update(Request $request, ShopCategory $category): RedirectResponse
    {
        $this->save($request, $category);

        return back();
    }

    public function destroy(ShopCategory $category): RedirectResponse
    {
        $category->delete();

        return back();
    }

    private function save(Request $request, ShopCategory $category): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $category->fill(['sort_order' => (int) ($data['sort_order'] ?? 0)] + collect($data)->except(['slug', 'image', 'sort_order'])->all());
        $category->slug = UniqueSlug::make(ShopCategory::class, ($data['slug'] ?? null) ?: $data['name'], $category->id);
        $this->replaceImage($request, $category);
        $category->save();
    }

    private function replaceImage(Request $request, ShopCategory $category): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        if ($category->image_path) {
            Storage::disk('public_uploads')->delete($category->image_path);
        }

        $category->image_path = $request->file('image')->store('shop/categories', 'public_uploads');
    }
}
