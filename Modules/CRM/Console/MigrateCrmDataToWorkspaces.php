<?php

namespace Modules\CRM\Console;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\LeadNote;
use Modules\CRM\Models\LeadTag;
use Modules\CRM\Models\Role;
use DB;

class MigrateCrmDataToWorkspaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:migrate-workspaces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates legacy CRM data (user_id based) to the new Workspace Architecture';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CRM Workspace Data Migration...');

        // Step 1: Ensure default roles exist for any created workspace
        // Usually, these would be seeded, but we'll create them dynamically for each workspace or globally.
        // Actually, Role table has workspace_id = nullable for global system roles.
        $this->seedSystemRoles();

        $ownerRole = Role::where('name', 'Owner')->where('is_system', true)->first();

        // Find all active CRM subscriptions to identify users who should get a workspace
        // Assuming module plan 'crm'
        $crmSubscriptions = UserSubscription::whereHas('plan', function ($q) {
            $q->where('module', 'crm');
        })->where('status', 'active')->get();

        if ($crmSubscriptions->isEmpty()) {
            $this->info('No active CRM subscriptions found. Proceeding to find users with existing leads.');
        }

        // Also find any users who have leads, even if no active subscription (to not lose data)
        // Note: 'user_id' might not exist on leads anymore if migration was run, but if they have a workspace, we skip.
        // Wait, if the user ran the migration, the 'user_id' column was dropped! 
        // If the 'user_id' column is dropped, we can't migrate the data easily!
        // That's why this command needs to run BEFORE the table modification migration, OR the table modification migration needs to handle it.
        // Since I wrote the Schema::dropColumn('user_id') in the same migration, data WILL BE LOST if run!
        
        $this->error('CRITICAL: This command must be integrated into the Database Migration directly to prevent data loss when user_id is dropped!');
        
        return Command::FAILURE;
    }

    private function seedSystemRoles()
    {
        $roles = ['Owner', 'Admin', 'Manager', 'Sales', 'Viewer'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'is_system' => true],
                ['workspace_id' => null]
            );
        }
    }
}
