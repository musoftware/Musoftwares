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

    public function __construct(FbmbLookupService $lookupService)
    {
        parent::__construct();
        $this->lookupService = $lookupService;
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

            try {
                if (!$record->input_path || !file_exists($record->input_path)) {
                    throw new \Exception("Uploaded input file not found on disk.");
                }

                $user = $record->user;
                if (!$user) {
                    throw new \Exception("User associated with this lookup does not exist.");
                }

                // Process file through service (runs query, writes results, debits points)
                $result = $this->lookupService->processFile($user, $record->input_path);

                // Update the database record on completion
                $record->update([
                    'found_count'       => $result['found_count'],
                    'credits_used'      => $result['found_count'],
                    'remaining_balance' => $user->fresh()->points_balance,
                    'result_path'       => $result['result_path'],
                    'status'            => 'completed',
                    'expires_at'        => now()->addHours(24),
                ]);

                $this->info("Successfully completed record ID: {$record->id}. Matches: {$result['found_count']}");

                // Delete the input file
                if (file_exists($record->input_path)) {
                    @unlink($record->input_path);
                }
            } catch (\Exception $e) {
                Log::error("Failed processing FBMB lookup record ID {$record->id}: " . $e->getMessage());
                $this->error("Error processing record ID {$record->id}: " . $e->getMessage());

                // Update database record on failure
                $record->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
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
