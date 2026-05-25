<?php

namespace Modules\Booking\app\Features\Recurring\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\app\Features\Recurring\Services\RecurringOccurrenceGenerator;
use Carbon\Carbon;

class GenerateFutureOccurrencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $seriesId;

    public function __construct(int $seriesId)
    {
        $this->seriesId = $seriesId;
    }

    public function handle(RecurringOccurrenceGenerator $generator): void
    {
        $series = RecurringSeries::find($this->seriesId);

        if (!$series || $series->status !== 'active') {
            return;
        }

        // Lazy generation: Always generate 30 days ahead of the current date
        $horizon = Carbon::now()->addDays(30);

        $generator->generateForSeries($series, $horizon);
    }
}
