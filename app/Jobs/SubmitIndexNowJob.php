<?php

namespace App\Jobs;

use App\Services\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Sends changed public pages to IndexNow. Many saves in a short time collapse into one run. */
class SubmitIndexNowJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function handle(IndexNowService $indexNow): void
    {
        if ($indexNow->enabled()) {
            $indexNow->submitChanged();
        }
    }
}
