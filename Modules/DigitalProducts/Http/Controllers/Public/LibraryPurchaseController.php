<?php

namespace Modules\DigitalProducts\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Services\LibraryCheckoutService;
use Exception;

class LibraryPurchaseController extends Controller
{
    public function __construct(
        protected LibraryCheckoutService $checkoutService
    ) {}

    /**
     * Purchase a paid book using Wallet balance.
     */
    public function purchaseWithWallet(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'يرجى تسجيل الدخول أولاً لإتمام الشراء.');
        }

        $product = DigitalProduct::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        try {
            $this->checkoutService->purchaseWithWallet($user, $product);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم شراء الكتاب بنجاح وتمت إضافته إلى مكتبتك!',
                    'redirect_url' => route('library.my_library'),
                ]);
            }

            return redirect()->route('library.my_library')
                ->with('success', 'تم شراء الكتاب بنجاح وتمت إضافته إلى مكتبتك!');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
