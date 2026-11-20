<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\SoftwareStore\Services\SystemsCopyRefresh;

/**
 * One-off: the store seeder never overwrites page text that exists, so the corrected copy of the first
 * 15 ready-made systems is pushed with this command. Without --apply it only prints what would change.
 */
class RefreshSystemsCopy extends Command
{
    protected $signature = 'store:refresh-systems-copy {--apply : Write the changes. Without it nothing is saved.}';

    protected $description = 'Replace the store copy of the 15 first ready-made systems where it still equals the copy seeded before; text edited in the admin is kept and listed';

    public function handle(SystemsCopyRefresh $refresh): int
    {
        $apply = (bool) $this->option('apply');
        $plan = $apply ? $refresh->apply() : $refresh->plan();

        if ($plan === []) {
            $this->info('Every system already has the current copy. Nothing to do.');

            return self::SUCCESS;
        }

        foreach ($plan as $slug => $changes) {
            $this->line("<comment>{$slug}</comment>");
            foreach ($changes as $key => $action) {
                $this->line("  {$key}: {$action}");
            }
        }

        $kept = collect($plan)->flatten()->filter(fn (string $action) => $action === SystemsCopyRefresh::KEEP)->count();
        $this->newLine();
        $this->info($apply ? 'Saved. Blocks marked "kept" were not changed.' : 'Dry run: nothing was saved. Run again with --apply to write the changes.');
        if ($kept > 0) {
            $this->warn("{$kept} block(s) were edited in the admin and are kept. Compare them with the new copy by hand if needed.");
        }

        return self::SUCCESS;
    }
}
