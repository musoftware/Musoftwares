<?php

namespace Modules\DigitalProducts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\DigitalProducts\Models\DigitalCategory;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Services\PdfProcessingService;

class AdminDigitalProductController extends Controller
{
    public function __construct(
        protected PdfProcessingService $pdfService
    ) {}

    public function index(Request $request): View
    {
        $query = DigitalProduct::with(['category', 'purchases']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = DigitalCategory::orderBy('name')->get();

        $stats = [
            'total_books' => DigitalProduct::count(),
            'total_downloads' => DigitalProduct::sum('download_count'),
            'total_free' => DigitalProduct::where('is_free', true)->count(),
            'total_paid' => DigitalProduct::where('is_free', false)->count(),
        ];

        return view('digitalproducts::admin.index', compact('products', 'categories', 'stats'));
    }

    public function create(): View
    {
        $categories = DigitalCategory::where('is_active', true)->orderBy('name')->get();
        return view('digitalproducts::admin.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:digital_products,slug',
            'pdf_file' => 'required|file|mimes:pdf|max:153600', // max 150MB
            'cover_data' => 'nullable|string', // Base64 data from PDF.js
            'cover_image' => 'nullable|image|max:10240',
            'category_id' => 'nullable|exists:digital_categories,id',
            'price' => 'nullable|numeric|min:0',
            'is_free' => 'nullable|boolean',
            'author_name' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|string|max:10',
            'language' => 'nullable|string|max:10',
            'page_count' => 'nullable|integer|min:1',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        // 1. Process and store PDF file
        $pdfResult = $this->pdfService->storePdf($request->file('pdf_file'));

        // 2. Process and store Cover Image (from client-side PDF.js canvas or manual upload)
        $coverPath = null;
        if ($request->filled('cover_data')) {
            $coverPath = $this->pdfService->storeCoverImage($request->cover_data);
        } elseif ($request->hasFile('cover_image')) {
            $coverPath = $this->pdfService->storeCoverImage($request->file('cover_image'));
        }

        $price = (float) ($request->price ?? 0);
        $isFree = $request->boolean('is_free') || ($price <= 0);

        $product = DigitalProduct::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $isFree ? 0 : $price,
            'is_free' => $isFree,
            'file_path' => $pdfResult['file_path'],
            'cover_image_path' => $coverPath,
            'file_size' => $pdfResult['file_size'],
            'page_count' => $validated['page_count'] ?? $pdfResult['page_count'] ?? 1,
            'author_name' => $validated['author_name'] ?? $pdfResult['author'] ?? config('app.name'),
            'publisher' => $validated['publisher'] ?? config('app.name'),
            'publication_year' => $validated['publication_year'] ?? date('Y'),
            'language' => $validated['language'] ?? 'ar',
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'is_featured' => $request->boolean('is_featured', false),
            'meta_title' => $validated['meta_title'] ?? $validated['title'],
            'meta_description' => $validated['meta_description'] ?? $validated['short_description'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفع وإضافة الكتاب بنجاح!',
                'redirect_url' => route('admin.digitalproducts.index'),
            ]);
        }

        return redirect()->route('admin.digitalproducts.index')
            ->with('success', 'تم رفع وإضافة الكتاب واستخراج البيانات بنجاح!');
    }

    public function edit(int $id): View
    {
        $product = DigitalProduct::findOrFail($id);
        $categories = DigitalCategory::where('is_active', true)->orderBy('name')->get();

        return view('digitalproducts::admin.edit', compact('product', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = DigitalProduct::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:digital_products,slug,' . $product->id,
            'category_id' => 'nullable|exists:digital_categories,id',
            'price' => 'nullable|numeric|min:0',
            'is_free' => 'nullable|boolean',
            'author_name' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|string|max:10',
            'language' => 'nullable|string|max:10',
            'page_count' => 'nullable|integer|min:1',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:153600',
            'cover_image' => 'nullable|image|max:10240',
        ]);

        // If new PDF uploaded
        if ($request->hasFile('pdf_file')) {
            if ($product->file_path && Storage::disk('local')->exists($product->file_path)) {
                Storage::disk('local')->delete($product->file_path);
            }
            $pdfResult = $this->pdfService->storePdf($request->file('pdf_file'));
            $product->file_path = $pdfResult['file_path'];
            $product->file_size = $pdfResult['file_size'];
            if (!$request->filled('page_count') && $pdfResult['page_count']) {
                $product->page_count = $pdfResult['page_count'];
            }
        }

        // If new cover uploaded
        if ($request->hasFile('cover_image')) {
            $coverPath = $this->pdfService->storeCoverImage($request->file('cover_image'));
            $product->cover_image_path = $coverPath;
        }

        $price = (float) ($request->price ?? 0);
        $isFree = $request->boolean('is_free') || ($price <= 0);

        $product->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'category_id' => $validated['category_id'] ?? null,
            'price' => $isFree ? 0 : $price,
            'is_free' => $isFree,
            'author_name' => $validated['author_name'] ?? $product->author_name,
            'publisher' => $validated['publisher'] ?? $product->publisher,
            'publication_year' => $validated['publication_year'] ?? $product->publication_year,
            'language' => $validated['language'] ?? $product->language,
            'page_count' => $validated['page_count'] ?? $product->page_count,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
            'meta_title' => $validated['meta_title'] ?? $validated['title'],
            'meta_description' => $validated['meta_description'] ?? $validated['short_description'] ?? null,
        ]);

        return redirect()->route('admin.digitalproducts.index')
            ->with('success', 'تم تحديث بيانات الكتاب بنجاح!');
    }

    public function togglePublish(int $id): JsonResponse
    {
        $product = DigitalProduct::findOrFail($id);
        $product->is_published = !$product->is_published;
        $product->save();

        return response()->json([
            'success' => true,
            'is_published' => $product->is_published,
            'message' => $product->is_published ? 'تم نشر الكتاب في المعرض.' : 'تم إخفاء الكتاب.',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = DigitalProduct::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.digitalproducts.index')
            ->with('success', 'تم حذف الكتاب بنجاح.');
    }
}
