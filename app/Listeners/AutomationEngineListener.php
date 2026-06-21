<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AutomationEngineListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the wildcard event.
     */
    public function handle(string $eventName, array $data): void
    {
        if (!str_starts_with($eventName, 'App\\Events\\') && !str_starts_with($eventName, 'Modules\\')) {
            return;
        }

        // Fetch active rules matching this event trigger
        // We use a try-catch to avoid breaking the application if DB is down or tables don't exist yet
        try {
            $rules = \App\Models\AutomationRule::where('event_trigger', $eventName)
                ->where('is_active', true)
                ->get();

            foreach ($rules as $rule) {
                \App\Jobs\EvaluateAutomationRuleJob::dispatch($rule, $data);
            }
        } catch (\Exception $e) {
            // Log or ignore if the table doesn't exist
            \Illuminate\Support\Facades\Log::error('AutomationEngineListener Error: ' . $e->getMessage());
        }
    }
}
