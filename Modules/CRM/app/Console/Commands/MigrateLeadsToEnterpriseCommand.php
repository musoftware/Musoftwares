<?php

namespace Modules\CRM\app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLeadsToEnterpriseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:migrate-leads-enterprise';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old generic CRM leads to the new enterprise pipeline structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of old leads to enterprise pipeline...');

        DB::table('leads')
            ->whereNull('pipeline_stage')
            ->orWhere('pipeline_stage', '')
            ->chunkById(100, function ($leads) {
                foreach ($leads as $lead) {
                    $newStage = 'NEW';
                    
                    // Basic heuristic mapping from old 'status' (assuming it exists) to 'pipeline_stage'
                    if (isset($lead->status)) {
                        $oldStatus = strtolower($lead->status);
                        if (in_array($oldStatus, ['won', 'converted', 'closed', 'customer', 'client'])) {
                            $newStage = 'WON';
                        } elseif (in_array($oldStatus, ['lost', 'rejected', 'junk', 'invalid'])) {
                            $newStage = 'LOST';
                        } elseif (in_array($oldStatus, ['interested', 'warm'])) {
                            $newStage = 'INTERESTED';
                        } elseif (in_array($oldStatus, ['contacted', 'follow_up'])) {
                            $newStage = 'FOLLOW_UP';
                        }
                    }

                    DB::table('leads')->where('id', $lead->id)->update([
                        'pipeline_stage' => $newStage,
                        'call_attempts' => 0,
                        'is_stale' => false,
                        // Update updated_at only if necessary
                    ]);
                }
                
                $this->info('Migrated 100 leads.');
            });

        $this->info('Lead migration to enterprise pipeline completed successfully.');
    }
}
