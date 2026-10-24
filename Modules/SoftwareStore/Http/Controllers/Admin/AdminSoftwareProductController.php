<?php

namespace Modules\SoftwareStore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialSoftware;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\SoftwareRelease;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminSoftwareProductController extends Controller
{
    private const MAX_INSTALLER_KB = 512000;

    /** Simple table: one row per program. Everything else is on the edit page. */
    public function index(): Response
    {
        $products = SoftwareProduct::withCount(['orders as paid_orders_count' => fn ($q) => $q->where('status', SoftwareOrder::STATUS_PAID)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SoftwareProduct $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name_en,
                'name_ar' => $p->name_ar,
                'image_url' => $p->imageUrl(),
                'price' => $p->startingPrice(),
                'has_plans' => $p->hasPlans(),
                'currency' => $p->saleCurrency(),
                'is_active' => $p->is_active,
                'version' => $p->latestRelease()?->version,
                'paid_orders' => $p->paid_orders_count,
                'page_url' => $p->purchaseUrl(),
            ]);

        return Inertia::render('Admin/SoftwareProducts/Index', [
            'products' => $products,
            'orders' => SoftwareOrder::with('product:id,name_en')->latest()->limit(20)->get(),
        ]);
    }

    public function edit(SoftwareProduct $product): Response
    {
        $product->load(['releases' => fn ($q) => $q->orderByDesc('released_at')->orderByDesc('id')]);

        return Inertia::render('Admin/SoftwareProducts/Edit', [
            'product' => $product->toArray() + [
                'image_url' => $product->imageUrl(),
                'video_file_url' => $product->videoFileUrl(),
                'poster_url' => $product->mediaUrl($product->poster_path),
                'gallery_items' => collect($product->galleryPaths())->map(fn ($path) => ['path' => $path, 'url' => $product->mediaUrl($path)])->all(),
                'plans' => array_values($product->plans ?? []),
                'landing' => $product->landing ?: new \stdClass,
                'sections' => $product->sectionOrder(),
                'colors' => $product->themeColors(),
                'feed_url' => url("/updates/{$product->slug}/latest.yml"),
                'page_url' => $product->purchaseUrl(),
            ],
            'allSections' => SoftwareProduct::ALL_SECTIONS,
            'serialSoftwares' => SerialSoftware::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function toggleActive(SoftwareProduct $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back();
    }

    public function update(Request $request, SoftwareProduct $product)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'tagline_en' => 'nullable|string|max:255',
            'tagline_ar' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'video_url' => 'nullable|url|max:500',
            'is_active' => 'required|boolean',
            'serial_software_id' => 'nullable|integer|exists:serial_softwares,id',
            'image' => 'nullable|image|max:5120',
            'video' => 'nullable|mimetypes:video/mp4,video/webm|max:204800',
            'poster' => 'nullable|image|max:5120',
            'gallery_add' => 'nullable|array|max:12',
            'gallery_add.*' => 'image|max:8192',
            'gallery_remove' => 'nullable|array',
            'gallery_remove.*' => 'string',
            'plans' => 'nullable|array|max:12',
            'plans.*.key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'plans.*.name' => 'required|string|max:120',
            'plans.*.name_ar' => 'nullable|string|max:120',
            'plans.*.description' => 'nullable|string|max:300',
            'plans.*.description_ar' => 'nullable|string|max:300',
            'plans.*.features' => 'nullable|array|max:12',
            'plans.*.features.*' => 'nullable|string|max:200',
            'plans.*.features_ar' => 'nullable|array|max:12',
            'plans.*.features_ar.*' => 'nullable|string|max:200',
            'plans.*.price' => 'required|numeric|min:0',
            'plans.*.points' => 'nullable|integer|min:0',
            'plans.*.bots' => 'nullable|integer|min:0',
            'landing' => 'nullable|array',
            'colors.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.ink' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sections' => 'nullable|array',
            'sections.*' => ['string', Rule::in(SoftwareProduct::ALL_SECTIONS)],
        ]);

        $skip = ['image', 'video', 'poster', 'gallery_add', 'gallery_remove', 'plans', 'landing', 'colors', 'sections'];
        $product->fill(collect($data)->except($skip)->all());
        $product->currency = strtoupper($data['currency']);
        $this->replaceMedia($product, 'image_path', $request->file('image'));
        $this->replaceMedia($product, 'video_path', $request->file('video'));
        $this->replaceMedia($product, 'poster_path', $request->file('poster'));
        $product->gallery = $this->updatedGallery($product, $request->file('gallery_add', []), $data['gallery_remove'] ?? []);
        $product->plans = $this->cleanPlans($data['plans'] ?? []) ?: null;
        // gallery_en (the English pictures) is not part of this form, so it must survive a save
        $product->theme = ['colors' => array_filter($data['colors'] ?? []), 'sections' => array_values($data['sections'] ?? [])]
            + array_intersect_key($product->theme ?? [], ['gallery_en' => true]);
        if (! empty($data['landing'])) {
            $product->landing = $data['landing'];
        }
        $product->save();
        $this->syncPriceToSerialSystem($product);

        return back()->with('success', __('software.saved'));
    }

    /**
     * The serial system is what the desktop program asks for its price and activation rules,
     * so the price typed here is written there too. One place to change it.
     */
    private function syncPriceToSerialSystem(SoftwareProduct $product): void
    {
        $software = $product->serialSoftware;
        if (! $software || (float) $product->price <= 0) {
            return;
        }

        $software->update([
            'requires_payment' => true,
            'pricing_type' => SerialSoftware::PRICING_SINGLE,
            'price' => $product->price,
            'currency' => $product->currency,
            'show_price' => true,
            'billing_cycle' => $software->billing_cycle === SerialSoftware::CYCLE_LIFETIME
                ? SerialSoftware::CYCLE_ANNUAL
                : $software->billing_cycle,
        ]);
    }

    /**
     * One step for the admin: pick the setup file (and its .blockmap if there is one).
     * The version is read from the file name unless the admin types it.
     */
    public function storeRelease(Request $request, SoftwareProduct $product)
    {
        $data = $request->validate([
            'files' => 'required|array|min:1|max:4',
            'files.*' => 'file|max:'.self::MAX_INSTALLER_KB,
            'version' => ['nullable', 'string', 'max:50', 'regex:/^\d+\.\d+\.\d+(-[\w.]+)?$/'],
            'release_notes' => 'nullable|string|max:5000',
        ]);

        $installer = $this->pickByExtension($request->file('files'), 'exe');
        if (! $installer) {
            return back()->withErrors(['files' => __('software.installer_missing')]);
        }

        $version = $data['version'] ?? $this->versionFromFileName($installer->getClientOriginalName());
        if (! $version) {
            return back()->withErrors(['version' => __('software.version_unknown')]);
        }

        if ($product->releases()->withTrashed()->where('version', $version)->exists()) {
            return back()->withErrors(['version' => __('software.version_exists')]);
        }

        $blockmap = $this->pickByExtension($request->file('files'), 'blockmap');
        $this->createRelease($product, $installer, $blockmap, $version, $data['release_notes'] ?? null);

        return back()->with('success', __('software.saved'));
    }

    private function createRelease(SoftwareProduct $product, UploadedFile $installer, ?UploadedFile $blockmap, string $version, ?string $notes): void
    {
        $fileName = "{$product->slug}-setup-{$version}.exe";
        $folder = "software-releases/{$product->slug}";
        $path = $installer->storeAs($folder, $fileName, 'local');

        $product->releases()->create([
            'version' => $version,
            'file_path' => $path,
            'file_name' => $fileName,
            'sha512' => base64_encode(hash_file('sha512', Storage::disk('local')->path($path), true)),
            'size' => Storage::disk('local')->size($path),
            'blockmap_path' => $this->storeBlockmap($blockmap, $folder, $fileName),
            'release_notes' => $notes,
            'is_published' => true,
            'released_at' => now(),
        ]);
    }

    /** @param UploadedFile[] $files */
    private function pickByExtension(array $files, string $extension): ?UploadedFile
    {
        foreach ($files as $file) {
            if (strtolower($file->getClientOriginalExtension()) === $extension) {
                return $file;
            }
        }

        return null;
    }

    private function versionFromFileName(string $name): ?string
    {
        return preg_match('/(\d+\.\d+\.\d+)/', $name, $m) ? $m[1] : null;
    }

    public function toggleRelease(SoftwareRelease $release)
    {
        $release->update(['is_published' => ! $release->is_published]);

        return back();
    }

    public function destroyRelease(SoftwareRelease $release)
    {
        $release->delete();

        return back()->with('success', __('software.deleted'));
    }

    /** @param UploadedFile[] $added */
    private function updatedGallery(SoftwareProduct $product, array $added, array $removed): array
    {
        $kept = array_values(array_diff($product->galleryPaths(), $removed));
        foreach (array_intersect($product->galleryPaths(), $removed) as $path) {
            $this->deleteStoredMedia($path);
        }

        $stored = array_map(fn (UploadedFile $file) => $file->store('software-products/gallery', 'public_uploads'), $added);

        return array_merge($kept, $stored);
    }

    private function deleteStoredMedia(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/')) {
            Storage::disk('public_uploads')->delete($path);
        }
    }

    private function storeBlockmap(?UploadedFile $file, string $folder, string $installerName): ?string
    {
        return $file?->storeAs($folder, $installerName.'.blockmap', 'local');
    }

    private function replaceMedia(SoftwareProduct $product, string $column, ?UploadedFile $file): void
    {
        if (! $file) {
            return;
        }

        $this->deleteStoredMedia($product->{$column});

        $product->{$column} = $file->store('software-products', 'public_uploads');
    }

    /** Drops blank feature lines that the plan editor leaves behind. */
    private function cleanPlans(array $plans): array
    {
        return array_values(array_map(function (array $plan) {
            foreach (['features', 'features_ar'] as $key) {
                $plan[$key] = array_values(array_filter($plan[$key] ?? [], fn ($line) => filled($line)));
            }

            return $plan;
        }, $plans));
    }
}
