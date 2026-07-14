<?php

namespace App\Jobs;

use App\Models\AutomationRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExecuteAutomationActionJob implements ShouldQueue
{
    use Queueable;

    public $rule;

    public $action;

    public $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(AutomationRule $rule, array $action, array $eventData)
    {
        $this->rule = $rule;
        $this->action = $action;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $type = $this->action['type'] ?? 'unknown';

        Log::info("Executing automation action [{$type}] for Rule ID: {$this->rule->id}", $this->eventData);

        switch ($type) {
            case 'send_email':
                $target = $this->action['target'] ?? $this->eventData['lead_email'] ?? null;
                $template = $this->action['template'] ?? 'System Notification';
                if ($target) {
                    try {
                        $userByEmail = \App\Models\User::where('email', $target)->first();
                        if ($userByEmail && ! ($userByEmail->enable_notifications ?? true)) {
                            Log::info("Automation email skipped: notifications are disabled for user/client email {$target}");
                            break;
                        }
                        Mail::raw("Automation triggered: {$template}", function ($message) use ($target) {
                            $message->to($target)->subject('Automation Notification');
                        });
                        Log::info("Automation email sent to {$target}");
                    } catch (\Exception $e) {
                        Log::error('Failed to send automation email: '.$e->getMessage());
                    }
                }
                break;

            case 'webhook':
                $url = $this->action['target'] ?? null;
                if ($url) {
                    try {
                        Http::post($url, $this->eventData);
                        Log::info("Automation webhook sent to {$url}");
                    } catch (\Exception $e) {
                        Log::error('Failed to send automation webhook: '.$e->getMessage());
                    }
                }
                break;
            default:
                Log::warning("Unknown automation action type: {$type}");
                break;
        }
    }
}
