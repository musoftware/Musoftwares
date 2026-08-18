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
            ->where('is_free', true)
            ->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = auth()->user();

        $downloadRecord = $this->downloadTokenService->generateTokenForFreeBook($product, $email, $user);

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
                'message' => 'تم إرسال رابط التحميل إلى بريدك الإلكتروني بنجاح!',
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
