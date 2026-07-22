<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Services\FreeDownloadService;

class FreeDownloadController extends Controller
{
    public function __construct(protected FreeDownloadService $freeDownloadService) {}

    public function requestDownload(Request $request, Service $service)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $download = $this->freeDownloadService->processLeadDownload(
                $service,
                $validated['email'],
                $validated['name'] ?? null,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Download token generated successfully.',
                'download_token' => $download->download_token,
                'claim_url' => route('marketplace.downloads.claim', ['token' => $download->download_token]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function claimDownload(string $token)
    {
        try {
            $download = $this->freeDownloadService->verifyAndClaimDownload($token);
            return response()->json([
                'success' => true,
                'message' => 'Download granted.',
                'service_id' => $download->service_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
