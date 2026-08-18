<?php

namespace Modules\DigitalProducts\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductPurchase;
use Modules\DigitalProducts\Services\DownloadTokenService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class MyLibraryController extends Controller
{
    public function __construct(
        protected DownloadTokenService $downloadTokenService
    ) {}

    /**
     * Display user's personal library of purchased and downloaded books.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $purchasedProducts = DigitalProduct::whereHas('purchases', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('category')->latest()->get();

        $meta = [
            'title' => 'مكتبتي الرقمية | كتبك المشتراة والمحملة - Musoftware',
            'description' => 'الوصول الدائم مدى الحياة لكافة الكتب الرقمية والأدلة التي قمت بشرائها وتحميلها.',
            'url' => route('library.my_library'),
            'type' => 'website',
        ];

        return view('digitalproducts::public.my-library', compact('purchasedProducts', 'meta'));
    }

    /**
     * Permanent authenticated download for a book.
     */
    public function download(Request $request, string $slug): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();
        $product = DigitalProduct::where('slug', $slug)
            ->firstOrFail();

        try {
            return $this->downloadTokenService->serveDownloadForPurchaser($user, $product);
        } catch (Exception $e) {
            return redirect()->route('library.my_library')
                ->with('error', $e->getMessage());
        }
    }
}
