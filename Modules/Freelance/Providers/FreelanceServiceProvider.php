<?php

namespace Modules\Freelance\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Freelance\Console\ExpireOldJobs;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Policies\JobPolicy;
use Modules\Freelance\Models\Proposal;
use Modules\Freelance\Policies\ProposalPolicy;
use Modules\Freelance\Models\Contract;
use Modules\Freelance\Policies\ContractPolicy;

class FreelanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            module_path('Freelance', 'Database/Migrations')
        );

        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(Proposal::class, ProposalPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireOldJobs::class,
            ]);
        }
    }
}
