<?php

namespace Modules\fbmb\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\fbmb\Services\FbmbLookupService;
use Exception;

class FbmbLookupController extends Controller
{
    protected FbmbLookupService $lookupService;

    public function __construct(FbmbLookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function index()
    {
        $user = auth()->user();
        return \Inertia\Inertia::render('Intelligence/ISaas/Index', [
            'pointsBalance' => $user->points_balance,
            'currency' => $user->preferred_currency ?? 'USD',
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:txt,csv|max:10240',
        ]);

        $user = auth()->user();
        $pointsBalance = $user->points_balance;

        if ($pointsBalance <= 0) {
            return response()->json([
                'message' => 'Insufficient points balance. Please get points first.',
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('temp_isaas');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        try {
            $result = $this->lookupService->processFile($user, $fullPath);

            // Store result path in session so the download route can serve it
            $downloadToken = md5(uniqid(mt_rand(), true));
            session()->put("isaas_download_{$downloadToken}", $result['result_path']);

            // Clean up uploaded file
            @unlink($fullPath);

            return response()->json([
                'success' => true,
                'total_ids' => $result['total_ids'],
                'found_count' => $result['found_count'],
                'credits_used' => $result['found_count'], // 1 point per match
                'remaining_balance' => $user->fresh()->points_balance,
                'download_token' => $downloadToken,
            ]);
        } catch (Exception $e) {
            @unlink($fullPath);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function download(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            abort(400, 'Missing download token.');
        }

        $path = session()->pull("isaas_download_{$token}");

        if (! $path || ! file_exists($path)) {
            abort(404, 'Download expired or not found.');
        }

        return response()->download($path, 'isaas_results.csv')->deleteFileAfterSend(true);
    }
}
