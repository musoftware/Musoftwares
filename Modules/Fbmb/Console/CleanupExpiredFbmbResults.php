<?php

namespace Modules\Fbmb\Console;

use Illuminate\Console\Command;
use Modules\Fbmb\Models\FbmbLookupResult;

class CleanupExpiredFbmbResults extends Command
{
    protected $signature   = 'fbmb:cleanup-expired';
    protected $description = 'Delete expired FBMB lookup result files and their DB records';

    public function handle(): int
    {
        $expired = FbmbLookupResult::where('expires_at', '<=', now())->get();

        $deleted = 0;
        foreach ($expired as $record) {
            // Delete the CSV file from disk if it still exists
            if ($record->result_path && file_exists($record->result_path)) {
                @unlink($record->result_path);
            }
            // Delete the uploaded input file if it still exists
            if ($record->input_path && file_exists($record->input_path)) {
                @unlink($record->input_path);
            }
            $record->delete();
            $deleted++;
        }

        $this->info("Cleaned up {$deleted} expired FBMB lookup result(s).");

        return self::SUCCESS;
    }
}
