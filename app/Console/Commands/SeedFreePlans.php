<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedFreePlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:seed-free-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Free Trial plans for all SaaS modules so users can test the platform legitimately without destroying logic.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = ['erp', 'freelance', 'marketing', 'marketplace', 'booking', 'intelligence'];

        foreach ($modules as $module) {
            \App\Models\ModulePlan::firstOrCreate(
                ['module' => $module, 'name' => 'Free Trial'],
                [
                    'price' => 0.00,
                    'billing' => 'monthly',
                    'features' => [
                        'projects' => -1,
                        'invoices' => -1,
                        'tasks' => -1,
                        'team_members' => 2,
                        'connects' => 50,
                        'commission_rate' => 10.0
                    ],
                    'is_active' => true,
                ]
            );
        }

        $this->info('Free Trial plans have been successfully seeded for all modules!');
    }
}
