<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillTenantClientUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:backfill-client-user-ids
                            {--dry-run : Preview matches without writing}
                            {--limit=0 : Limit number of records to process (0 = all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill tenant_clients.user_id by matching email addresses from the users table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dryRun
            ? 'Dry-run: previewing matches...'
            : 'Backfilling tenant_clients.user_id...'
        );

        // Fetch all tenant_clients where user_id is still NULL and email is set
        $query = DB::table('erp_tenant_clients')
            ->whereNull('user_id')
            ->whereNotNull('email');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $clients = $query->get();

        $this->info("Found {$clients->count()} client(s) without user_id.");

        $matched = 0;
        $unmatched = 0;
        $rows = [];

        foreach ($clients as $client) {
            // Look up a user by email — exact match
            $user = DB::table('users')
                ->where('email', $client->email)
                ->value('id');

            if ($user) {
                $rows[] = [$client->id, $client->email, $user, 'MATCH'];
                $matched++;

                if (! $dryRun) {
                    DB::table('erp_tenant_clients')
                        ->where('id', $client->id)
                        ->update(['user_id' => $user]);
                }
            } else {
                $rows[] = [$client->id, $client->email, '—', 'NO MATCH'];
                $unmatched++;
            }
        }

        $this->table(
            ['Client ID', 'Email', 'Matched User ID', 'Status'],
            $rows
        );

        $this->info("Matched: {$matched} | Unmatched: {$unmatched}");

        if (! $dryRun && $matched > 0) {
            Log::info("erp:backfill-client-user-ids: updated {$matched} tenant_client records.");
            $this->info("{$matched} record(s) updated successfully.");
        }

        return self::SUCCESS;
    }
}
