<?php

namespace Modules\Fbmb\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Fbmb\Models\FbmbLookupResult;
use Modules\Fbmb\Services\FbmbLookupService;
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

        $history = FbmbLookupResult::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id'                => $r->id,
                'total_ids'         => $r->total_ids,
                'found_count'       => $r->found_count,
                'credits_used'      => $r->credits_used,
                'remaining_balance' => $r->remaining_balance,
                'download_token'    => $r->download_token,
                'expired'           => $r->isExpired(),
                'file_exists'       => $r->fileExists(),
                'created_at'        => $r->created_at->toISOString(),
                'expires_at'        => $r->expires_at->toISOString(),
            ]);

        return \Inertia\Inertia::render('Fbmb/Index', [
            'pointsBalance' => $user->points_balance,
            'currency'      => $user->preferred_currency ?? 'USD',
            'history'       => $history,
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

        $file     = $request->file('file');
        $path     = $file->store('temp_isaas');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        try {
            $result = $this->lookupService->processFile($user, $fullPath);

            $downloadToken = md5(uniqid(mt_rand(), true));

            // Persist to database — survives page refresh indefinitely until cron cleans up
            FbmbLookupResult::create([
                'user_id'           => $user->id,
                'download_token'    => $downloadToken,
                'total_ids'         => $result['total_ids'],
                'found_count'       => $result['found_count'],
                'credits_used'      => $result['found_count'],
                'remaining_balance' => $user->fresh()->points_balance,
                'result_path'       => $result['result_path'],
                'expires_at'        => now()->addHours(24),
            ]);

            // Clean up the uploaded (input) file — the result CSV stays
            @unlink($fullPath);

            return response()->json([
                'success'           => true,
                'total_ids'         => $result['total_ids'],
                'found_count'       => $result['found_count'],
                'credits_used'      => $result['found_count'],
                'remaining_balance' => $user->fresh()->points_balance,
                'download_token'    => $downloadToken,
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
            abort(400, __('general.missing_download_token'));
        }

        $record = FbmbLookupResult::where('download_token', $token)
            ->where('user_id', auth()->id())
            ->first();

        if (! $record) {
            abort(404, __('general.download_link_not_found'));
        }

        if ($record->isExpired()) {
            abort(410, __('general.this_download_link_has_expired_24_hours_please_run_a_new_lookup'));
        }

        if (! $record->fileExists()) {
            abort(404, __('general.result_file_not_found_it_may_have_been_cleaned_up'));
        }

        return response()->download($record->result_path, 'isaas_results.csv');
    }
}
