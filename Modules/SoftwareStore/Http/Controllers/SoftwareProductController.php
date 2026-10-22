<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\StoreSeo;

class SoftwareProductController extends Controller
{
    /** Old address of a program that was renamed. */
    private const RENAMED = ['amc-tasks' => 'marketing-suite'];

    public function index()
    {
        $products = SoftwareProduct::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groups = collect(['programs', 'systems', 'services'])
            ->mapWithKeys(fn (string $key) => [$key => $products->filter(fn (SoftwareProduct $product) => $product->group() === $key)])
            ->filter(fn ($items) => $items->isNotEmpty());

        return view('softwarestore::index', compact('products', 'groups'));
    }

    /** The page as plain Markdown, for AI assistants. */
    public function markdown(string $slug, StoreSeo $seo)
    {
        $product = SoftwareProduct::where('is_active', true)->where('slug', $slug)->firstOrFail();

        return response($seo->markdown($product), 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Link' => '<'.$product->purchaseUrl().'>; rel="canonical"',
        ]);
    }

    public function show(string $slug)
    {
        if (isset(self::RENAMED[$slug])) {
            return redirect()->route('website-services.show', self::RENAMED[$slug], 301);
        }

        $product = SoftwareProduct::where('is_active', true)->where('slug', $slug)->first();

        // The older company-services page shares this URL pattern, so fall back to it.
        if (! $product) {
            return app(HomeController::class)->websiteServiceShow($slug);
        }

        return view('softwarestore::show', ['product' => $product, 'latest' => $product->latestRelease()]);
    }
}
