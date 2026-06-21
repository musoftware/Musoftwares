<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteAutomationActionJob implements ShouldQueue
{
    use Queueable;

    public $rule;
    public $action;
    public $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\AutomationRule $rule, array $action, array $eventData)
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

        \Illuminate\Support\Facades\Log::info("Executing automation action [{$type}] for Rule ID: {$this->rule->id}", $this->eventData);

        switch ($type) {
            case 'send_email':
                $target = $this->action['target'] ?? $this->eventData['lead_email'] ?? null;
                $template = $this->action['template'] ?? 'System Notification';
                if ($target) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw("Automation triggered: {$template}", function ($message) use ($target) {
                            $message->to($target)->subject('Automation Notification');
                        });
                        \Illuminate\Support\Facades\Log::info("Automation email sent to {$target}");
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send automation email: " . $e->getMessage());
                    }
                }
                break;
            case 'update_tag':
                $tagName = $this->action['target'] ?? 'Automation';
                $leadId = $this->eventData['lead_id'] ?? null;
                if ($leadId) {
                    $lead = \Modules\CRM\Models\Lead::find($leadId);
                    if ($lead) {
                        $tag = \Modules\CRM\Models\LeadTag::firstOrCreate([
                            'name' => $tagName,
                            'workspace_id' => $lead->workspace_id,
                        ], [
                            'color' => '#3b82f6'
                        ]);
                        $lead->tags()->syncWithoutDetaching([$tag->id]);
                        \Illuminate\Support\Facades\Log::info("Tag {$tagName} added to Lead ID {$lead->id}");
                    }
                }
                break;
            case 'webhook':
                $url = $this->action['target'] ?? null;
                if ($url) {
                    try {
                        \Illuminate\Support\Facades\Http::post($url, $this->eventData);
                        \Illuminate\Support\Facades\Log::info("Automation webhook sent to {$url}");
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send automation webhook: " . $e->getMessage());
                    }
                }
                break;
            default:
                \Illuminate\Support\Facades\Log::warning("Unknown automation action type: {$type}");
                break;
        }
    }
}
