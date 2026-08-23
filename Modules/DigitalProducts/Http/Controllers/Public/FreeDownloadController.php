<?php

namespace Modules\DigitalProducts\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\DigitalProducts\Emails\FreeBookDownloadMail;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Services\DownloadTokenService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class FreeDownloadController extends Controller
{
    public function __construct(
        protected DownloadTokenService $downloadTokenService
    ) {}

    /**
     * Request free book download link via email.
     */
    public function requestLink(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $product = DigitalProduct::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = $request->user();

        $validated = $request->validate([
            'email' => $user ? 'nullable|email|max:255' : 'required|email|max:255',
            'edition_type' => 'nullable|string|in:full,playbook',
        ]);

        $email = !empty($validated['email'])
            ? strtolower(trim($validated['email']))
            : ($user ? strtolower(trim($user->email)) : null);

        if (empty($email)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'البريد الإلكتروني مطلوب لتحميل الكتاب.',
                ], 422);
            }
            return back()->with('error', 'البريد الإلكتروني مطلوب لتحميل الكتاب.');
        }

        $editionType = $validated['edition_type'] ?? ($product->is_free ? 'full' : 'playbook');

        if ($editionType === 'full' && !$product->is_free) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'النسخة الكاملة من هذا الكتاب مدفوعة. يمكنك تحميل نسخة الـ Playbook مجاناً.',
                ], 422);
            }
            return back()->with('error', 'النسخة الكاملة من هذا الكتاب مدفوعة. يمكنك تحميل نسخة الـ Playbook مجاناً.');
        }

        if ($editionType === 'playbook' && !$product->has_free_edition) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'نسخة الـ Playbook غير متوفرة لهذا الكتاب.',
                ], 422);
            }
            return back()->with('error', 'نسخة الـ Playbook غير متوفرة لهذا الكتاب.');
        }

        $downloadRecord = $this->downloadTokenService->generateTokenForFreeBook($product, $email, $user, $editionType);

        // If user is authenticated, record ownership so it appears in My Library permanently
        if ($user) {
            \Modules\DigitalProducts\Models\DigitalProductPurchase::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'digital_product_id' => $product->id,
                ],
                [
                    'amount_paid' => 0.00,
                    'currency_id' => $user->currency_id ?? 1,
                    'payment_method' => $editionType === 'playbook' ? 'free_playbook' : 'free',
                    'status' => 'completed',
                ]
            );
        }

        // Attempt sending email with download link
        try {
            Mail::to($email)->queue(new FreeBookDownloadMail($product, $downloadRecord));
        } catch (\Throwable $e) {
            // If mail fails or is queued, proceed gracefully
            report($e);
        }

        $directDownloadUrl = route('library.download.token', ['token' => $downloadRecord->download_token]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء رابط التحميل وإرسال نسخة إلى بريدك بنجاح!',
                'download_url' => $directDownloadUrl,
            ]);
        }

        return redirect($directDownloadUrl);
    }

    /**
     * Download book directly via secure token.
     */
    public function downloadByToken(string $token): BinaryFileResponse|RedirectResponse
    {
        try {
            return $this->downloadTokenService->serveDownloadByToken($token);
        } catch (Exception $e) {
            return redirect()->route('library.index')
                ->with('error', $e->getMessage());
        }
    }
}
