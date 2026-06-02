<?php

namespace Modules\Fbmb\Console;

use Illuminate\Console\Command;
use Modules\Fbmb\Models\FbmbLookupResult;
use Modules\Fbmb\Services\FbmbLookupService;
use Illuminate\Support\Facades\Log;

class ProcessPendingFbmbLookups extends Command
{
    protected $signature   = 'fbmb:process-pending';
    protected $description = 'Process pending FBMB lookup requests';

    protected FbmbLookupService $lookupService;
    protected \App\Services\PointsService $pointsService;

    public function __construct(FbmbLookupService $lookupService, \App\Services\PointsService $pointsService)
    {
        parent::__construct();
        $this->lookupService = $lookupService;
        $this->pointsService = $pointsService;
    }

    public function handle(): int
    {
        $pendingRecords = FbmbLookupResult::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($pendingRecords->isEmpty()) {
            $this->info("No pending FBMB lookup requests found.");
            return self::SUCCESS;
        }

        $this->info("Found " . $pendingRecords->count() . " pending request(s) to process.");

        foreach ($pendingRecords as $record) {
            $this->info("Processing record ID: {$record->id} for user ID: {$record->user_id}");

            // Transition status to processing
            $record->update(['status' => 'processing']);

            $user = $record->user;

            try {
                if (!$user) {
                    throw new \Exception("User associated with this lookup does not exist.");
                }

                if (!$record->input_path || !file_exists($record->input_path)) {
                    throw new \Exception("Uploaded input file not found on disk.");
                }

                // Process file through service (runs query, writes results)
                $result = $this->lookupService->processFile($user, $record->input_path);

                $foundCount = $result['found_count'];
                $totalIds = $record->total_ids;
                $refund = $totalIds - $foundCount;

                if ($refund > 0) {
                    $this->pointsService->credit(
                        $user,
                        $refund,
                        'fbmb_lookup_refund',
                        __('general.fbmb_lookup_refund_points', [
                            'refund' => $refund,
                            'found'  => $foundCount,
                            'total'  => $totalIds,
                        ])
                    );
                }

                // Update the database record on completion
                $record->update([
                    'found_count'       => $foundCount,
                    'credits_used'      => $foundCount,
                    'remaining_balance' => $user->fresh()->points_balance,
                    'result_path'       => $result['result_path'],
                    'status'            => 'completed',
                    'expires_at'        => now()->addHours(24),
                ]);

                $this->info("Successfully completed record ID: {$record->id}. Matches: {$foundCount}");

                // Delete the input file
                if (file_exists($record->input_path)) {
                    @unlink($record->input_path);
                }
            } catch (\Exception $e) {
                Log::error("Failed processing FBMB lookup record ID {$record->id}: " . $e->getMessage());
                $this->error("Error processing record ID {$record->id}: " . $e->getMessage());

                // Refund full points on failure
                try {
                    $refund = $record->total_ids;
                    $this->pointsService->credit(
                        $user,
                        $refund,
                        'fbmb_lookup_failure_refund',
                        __('general.fbmb_lookup_failure_refund_points', [
                            'refund' => $refund,
                            'total'  => $record->total_ids,
                        ])
                    );
                } catch (\Exception $refundEx) {
                    Log::error("Failed to refund points for failed FBMB lookup ID {$record->id}: " . $refundEx->getMessage());
                }

                // Update database record on failure
                $record->update([
                    'status'            => 'failed',
                    'credits_used'      => 0,
                    'remaining_balance' => $user ? $user->fresh()->points_balance : $record->remaining_balance,
                    'error_message'     => $e->getMessage(),
                ]);

                // Clean up input file to avoid leaking disk space
                if ($record->input_path && file_exists($record->input_path)) {
                    @unlink($record->input_path);
                }
            }
        }

        return self::SUCCESS;
    }
}
