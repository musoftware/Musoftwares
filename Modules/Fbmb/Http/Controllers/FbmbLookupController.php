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
    protected \App\Services\PointsService $pointsService;

    public function __construct(FbmbLookupService $lookupService, \App\Services\PointsService $pointsService)
    {
        $this->lookupService = $lookupService;
        $this->pointsService = $pointsService;
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
                'status'            => $r->status,
                'error_message'     => $r->error_message,
            ]);

        return \Inertia\Inertia::render('Fbmb/Index', [
            'pointsBalance' => $user->points_balance,
            'currency'      => $user->currency_id ? (\App\Models\Currency::find($user->currency_id)?->currency) : null,
            'history'       => $history,
            'pricingTiers'  => $this->lookupService->getPricingTiers(),
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
                'message' => __('general.insufficient_points_balance_please_get_points_first'),
            ], 422);
        }

        $file     = $request->file('file');
        $path     = $file->store('fbmb_inputs');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        try {
            $totalIds = $this->lookupService->countIds($fullPath);

            if ($totalIds <= 0) {
                @unlink($fullPath);
                return response()->json([
                    'message' => __('general.no_valid_ids_found_in_the_uploaded_file'),
                ], 400);
            }

            $estimatedCost = $this->lookupService->calculateCost($totalIds, $totalIds);

            if ($pointsBalance < $estimatedCost) {
                @unlink($fullPath);
                return response()->json([
                    'message' => __('general.insufficient_points_for_lookup', [
                        'required'  => $estimatedCost,
                        'available' => $pointsBalance,
                        'total'     => $totalIds,
                    ]),
                ], 422);
            }

            // Debit points up-front
            $this->pointsService->debit(
                $user,
                $estimatedCost,
                'fbmb_lookup_reserve',
                __('general.fbmb_lookup_reserve_points', ['total' => $totalIds])
            );

            $downloadToken = md5(uniqid(mt_rand(), true));

            // Persist to database as pending
            FbmbLookupResult::create([
                'user_id'           => $user->id,
                'download_token'    => $downloadToken,
                'total_ids'         => $totalIds,
                'found_count'       => 0,
                'credits_used'      => $estimatedCost,
                'remaining_balance' => $user->fresh()->points_balance,
                'input_path'        => $fullPath,
                'result_path'       => null,
                'status'            => 'pending',
                'expires_at'        => now()->addHours(24),
            ]);

            return response()->json([
                'success'        => true,
                'status'         => 'pending',
                'download_token' => $downloadToken,
            ]);
        } catch (Exception $e) {
            @unlink($fullPath);
            return response()->json([
                'message' => __('general.failed_to_queue_lookup_please_try_again'),
            ], 400);
        }
    }

    public function status($token)
    {
        $record = FbmbLookupResult::where('download_token', $token)
            ->where('user_id', auth()->id())
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        return response()->json([
            'id'                => $record->id,
            'total_ids'         => $record->total_ids,
            'found_count'       => $record->found_count,
            'credits_used'      => $record->credits_used,
            'remaining_balance' => $record->remaining_balance,
            'download_token'    => $record->download_token,
            'expired'           => $record->isExpired(),
            'file_exists'       => $record->fileExists(),
            'status'            => $record->status,
            'error_message'     => $record->error_message,
        ]);
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
