<?php

namespace App\Jobs;

use App\Mail\LoyaltyProgressMail;
use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLoyaltyProgressEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $userId,
        private readonly int $nextTierId,
        private readonly int $progressPct,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $nextTier = LoyaltyTier::find($this->nextTierId);

        if ($user === null || $nextTier === null || empty($user->email)) {
            return;
        }

        Mail::to($user->email)->send(
            new LoyaltyProgressMail($user, $nextTier, $this->progressPct)
        );
    }
}
