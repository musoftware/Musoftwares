<?php

namespace App\Console\Commands;

use App\Services\WinbackService;
use Illuminate\Console\Command;

class ProcessWinbackCampaigns extends Command
{
    protected $signature = 'winback:process';

    protected $description = 'Check all inactive clients and dispatch the appropriate win-back stage emails.';

    public function __construct(private readonly WinbackService $winbackService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->winbackService->dispatchPendingStages();

        $this->info('Win-back campaign jobs dispatched.');

        return self::SUCCESS;
    }
}
