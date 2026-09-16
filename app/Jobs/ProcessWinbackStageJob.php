<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WinbackStage;
use App\Services\WinbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWinbackStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(
        private readonly int $userId,
        private readonly int $stageId,
    ) {}

    public function handle(WinbackService $winbackService): void
    {
        $user = User::with(['loyaltyTier', 'tickets'])->find($this->userId);
        $stage = WinbackStage::with('incentiveRule')->find($this->stageId);

        if ($user === null || $stage === null) {
            return;
        }

        $winbackService->sendStageToUser($user, $stage);
    }
}
