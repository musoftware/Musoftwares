<?php

namespace Modules\DigitalProducts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DigitalProducts\Models\DigitalCategory;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Services\PdfProcessingService;

class AdminDigitalProductController extends Controller
{
    public function __construct(
        protected PdfProcessingService $pdfService
    ) {}

    public function index(Request $request): Response
    {
        $query = DigitalProduct::with(['category', 'currency', 'purchases']);

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
        $currencies = Currency::all();

        $stats = [
            'total_books' => DigitalProduct::count(),
            'total_downloads' => (int) DigitalProduct::sum('download_count'),
            'total_free' => DigitalProduct::where('is_free', true)->count(),
            'total_paid' => DigitalProduct::where('is_free', false)->count(),
        ];

        return Inertia::render('Admin/DigitalProducts/Index', [
            'products' => $products,
            'categories' => $categories,
            'currencies' => $currencies,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        $categories = DigitalCategory::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::all();

        return Inertia::render('Admin/DigitalProducts/Create', [
            'categories' => $categories,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:digital_products,slug',
            'pdf_file' => 'required|file|mimes:pdf|max:153600', // max 150MB
            'cover_data' => 'nullable|string', // Base64 data from PDF.js
            'cover_image' => 'nullable|image|max:10240',
            'has_free_edition' => 'nullable|boolean',
            'free_edition_title' => 'nullable|string|max:255',
            'free_edition_pdf_file' => 'nullable|file|mimes:pdf|max:153600',
            'free_edition_cover_data' => 'nullable|string',
            'free_edition_cover_image' => 'nullable|image|max:10240',
            'free_edition_page_count' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:digital_categories,id',
            'price' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
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

        // 1. Process and store Main PDF file
        $pdfResult = $this->pdfService->storePdf($request->file('pdf_file'));

        // 2. Process and store Main Cover Image
        $coverPath = null;
        if ($request->filled('cover_data')) {
            $coverPath = $this->pdfService->storeCoverImage($request->cover_data);
        } elseif ($request->hasFile('cover_image')) {
            $coverPath = $this->pdfService->storeCoverImage($request->file('cover_image'));
        }

        // 3. Process Free Playbook Edition if enabled
        $hasFreeEdition = $request->boolean('has_free_edition');
        $freeEditionFilePath = null;
        $freeEditionCoverPath = null;
        $freeEditionPageCount = null;
        $freeEditionFileSize = null;

        if ($hasFreeEdition && $request->hasFile('free_edition_pdf_file')) {
            $playbookResult = $this->pdfService->storePdf($request->file('free_edition_pdf_file'));
            $freeEditionFilePath = $playbookResult['file_path'];
            $freeEditionFileSize = $playbookResult['file_size'];
            $freeEditionPageCount = $validated['free_edition_page_count'] ?? $playbookResult['page_count'] ?? 1;

            if ($request->filled('free_edition_cover_data')) {
                $freeEditionCoverPath = $this->pdfService->storeCoverImage($request->free_edition_cover_data);
            } elseif ($request->hasFile('free_edition_cover_image')) {
                $freeEditionCoverPath = $this->pdfService->storeCoverImage($request->file('free_edition_cover_image'));
            }
        }

        $price = (float) ($request->price ?? 0);
        $isFree = $request->boolean('is_free') || ($price <= 0);

        $product = DigitalProduct::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $isFree ? 0 : $price,
            'currency_id' => $validated['currency_id'] ?? 1,
            'is_free' => $isFree,
            'has_free_edition' => $hasFreeEdition && !empty($freeEditionFilePath),
            'free_edition_title' => $validated['free_edition_title'] ?? 'Playbook Edition (ملخص مجاني)',
            'free_edition_file_path' => $freeEditionFilePath,
            'free_edition_cover_path' => $freeEditionCoverPath,
            'free_edition_page_count' => $freeEditionPageCount,
            'free_edition_file_size' => $freeEditionFileSize,
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
                'message' => 'تم رفع وإضافة الكتاب والنسخ بنجاح!',
                'redirect_url' => route('admin.digitalproducts.index'),
            ]);
        }

        return redirect()->route('admin.digitalproducts.index')
            ->with('success', 'تم رفع وإضافة الكتاب والنسخ واستخراج البيانات بنجاح!');
    }

    public function edit(int $id): Response
    {
        $product = DigitalProduct::with('currency')->findOrFail($id);
        $categories = DigitalCategory::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::all();

        return Inertia::render('Admin/DigitalProducts/Edit', [
            'product' => $product,
            'categories' => $categories,
            'currencies' => $currencies,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = DigitalProduct::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:digital_products,slug,' . $product->id,
            'category_id' => 'nullable|exists:digital_categories,id',
            'price' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'is_free' => 'nullable|boolean',
            'has_free_edition' => 'nullable|boolean',
            'free_edition_title' => 'nullable|string|max:255',
            'free_edition_pdf_file' => 'nullable|file|mimes:pdf|max:153600',
            'free_edition_cover_image' => 'nullable|image|max:10240',
            'free_edition_page_count' => 'nullable|integer|min:1',
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

        // If new Main PDF uploaded
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

        // If new Main cover uploaded
        if ($request->hasFile('cover_image')) {
            $coverPath = $this->pdfService->storeCoverImage($request->file('cover_image'));
            $product->cover_image_path = $coverPath;
        }

        // Free Playbook updates
        $hasFreeEdition = $request->boolean('has_free_edition');
        if ($hasFreeEdition) {
            if ($request->hasFile('free_edition_pdf_file')) {
                if ($product->free_edition_file_path && Storage::disk('local')->exists($product->free_edition_file_path)) {
                    Storage::disk('local')->delete($product->free_edition_file_path);
                }
                $playbookResult = $this->pdfService->storePdf($request->file('free_edition_pdf_file'));
                $product->free_edition_file_path = $playbookResult['file_path'];
                $product->free_edition_file_size = $playbookResult['file_size'];
                if (!$request->filled('free_edition_page_count') && $playbookResult['page_count']) {
                    $product->free_edition_page_count = $playbookResult['page_count'];
                }
            }
            if ($request->hasFile('free_edition_cover_image')) {
                $product->free_edition_cover_path = $this->pdfService->storeCoverImage($request->file('free_edition_cover_image'));
            }
            $product->has_free_edition = true;
            $product->free_edition_title = $validated['free_edition_title'] ?? $product->free_edition_title ?? 'Playbook Edition (ملخص مجاني)';
            if ($request->filled('free_edition_page_count')) {
                $product->free_edition_page_count = (int)$validated['free_edition_page_count'];
            }
        } else {
            $product->has_free_edition = false;
        }

        $price = (float) ($request->price ?? 0);
        $isFree = $request->boolean('is_free') || ($price <= 0);

        $product->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'category_id' => $validated['category_id'] ?? null,
            'price' => $isFree ? 0 : $price,
            'currency_id' => $validated['currency_id'] ?? $product->currency_id ?? 1,
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

    public function togglePublish(int $id): RedirectResponse|JsonResponse
    {
        $product = DigitalProduct::findOrFail($id);
        $product->is_published = !$product->is_published;
        $product->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_published' => $product->is_published,
                'message' => $product->is_published ? 'تم نشر الكتاب في المعرض.' : 'تم إخفاء الكتاب.',
            ]);
        }

        return back()->with('success', $product->is_published ? 'تم نشر الكتاب في المعرض.' : 'تم إخفاء الكتاب.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = DigitalProduct::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.digitalproducts.index')
            ->with('success', 'تم حذف الكتاب بنجاح.');
    }
}
