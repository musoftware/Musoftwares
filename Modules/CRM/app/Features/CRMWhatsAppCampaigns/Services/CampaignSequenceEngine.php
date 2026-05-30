<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignSequenceTriggered;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\ProcessSequenceStepJob;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;

class CampaignSequenceEngine
{
    public function __construct(
        protected WhatsAppTemplateRenderer $templateRenderer,
    ) {}

    /**
     * Start a sequence for all campaign audience members.
     */
    public function startSequence(WhatsAppCampaign $campaign, WhatsAppCampaignSequence $sequence): void
    {
        if (!$sequence->is_active) {
            return;
        }

        $firstStep = $sequence->getFirstStep();
        if (!$firstStep) {
            return;
        }

        event(new WhatsAppCampaignSequenceTriggered($campaign->workspace_id, $campaign, $sequence));

        // Schedule first step for all pending deliveries
        $campaign->deliveries()
            ->where('status', 'pending')
            ->whereNull('sequence_step_id')
            ->cursor()
            ->each(function (WhatsAppCampaignDelivery $delivery) use ($firstStep) {
                $this->scheduleStep($delivery, $firstStep);
            });
    }

    /**
     * Schedule a specific step for a delivery recipient.
     */
    public function scheduleStep(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        // Update delivery to reference this step
        $delivery->update(['sequence_step_id' => $step->id]);

        $delay = $step->getDelayInMinutes();

        if ($delay > 0) {
            ProcessSequenceStepJob::dispatch($delivery, $step)
                ->delay(now()->addMinutes($delay));
        } else {
            ProcessSequenceStepJob::dispatch($delivery, $step);
        }
    }

    /**
     * Execute a sequence step for a specific delivery.
     */
    public function executeStep(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        $campaign = $delivery->campaign;

        // Check if campaign is still running
        if (!$campaign->isRunning()) {
            return;
        }

        // Check exit conditions
        if ($this->shouldExit($delivery, $step)) {
            $delivery->update(['status' => 'skipped']);
            return;
        }

        // Check skip conditions
        if ($step->skip_if_replied && $delivery->has_replied) {
            $this->advanceToNextStep($delivery, $step);
            return;
        }

        match ($step->action_type) {
            'send_message' => $this->executeSendMessage($delivery, $step),
            'wait'         => $this->executeWait($delivery, $step),
            'condition'    => $this->executeCondition($delivery, $step),
            'update_lead'  => $this->executeUpdateLead($delivery, $step),
            'add_tag'      => $this->executeAddTag($delivery, $step),
            'remove_tag'   => $this->executeRemoveTag($delivery, $step),
            'exit'         => $delivery->update(['status' => 'skipped']),
            default        => null,
        };

        // Auto-advance for non-message steps (messages advance on send confirmation)
        if (!in_array($step->action_type, ['send_message', 'exit'])) {
            $this->advanceToNextStep($delivery, $step);
        }
    }

    /**
     * Check if the sequence should exit for this delivery.
     */
    protected function shouldExit(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): bool
    {
        $sequence = $step->sequence;
        $exitConditions = $sequence->exit_conditions ?? [];

        foreach ($exitConditions as $condition) {
            $field = $condition['field'] ?? null;

            $matches = match ($field) {
                'replied'   => $delivery->has_replied,
                'clicked'   => $delivery->has_clicked,
                'opted_out' => $delivery->has_opted_out,
                'delivered' => $delivery->status === 'delivered',
                'read'      => $delivery->status === 'read',
                default     => false,
            };

            if ($matches) return true;
        }

        // Check stop_on_reply
        if ($step->stop_on_reply && $delivery->has_replied) {
            return true;
        }

        return false;
    }

    /**
     * Execute a send_message step.
     */
    protected function executeSendMessage(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        $body = $step->message_body;

        // Use template if specified
        if ($step->template_id && $step->template) {
            $body = $step->template->body;
        }

        // Render placeholders
        $mergeData = $delivery->metadata['merge_data'] ?? [];
        $renderedBody = $this->templateRenderer->render($body ?? '', $mergeData);

        $delivery->update([
            'rendered_body' => $renderedBody,
            'message_type'  => $step->message_type,
            'status'        => 'queued',
            'queued_at'     => now(),
        ]);
    }

    /**
     * Execute a wait step (the delay is handled by the job scheduler).
     */
    protected function executeWait(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        // Wait is handled by delay in scheduleStep — this just advances
    }

    /**
     * Execute a condition step (branching).
     */
    protected function executeCondition(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        $conditions = $step->conditions ?? [];
        $conditionMet = $this->evaluateConditions($delivery, $conditions);

        $nextStepOrder = $conditionMet ? $step->on_true_step : $step->on_false_step;

        if ($nextStepOrder) {
            $nextStep = WhatsAppCampaignSequenceStep::where('sequence_id', $step->sequence_id)
                ->where('step_order', $nextStepOrder)->first();

            if ($nextStep) {
                $this->scheduleStep($delivery, $nextStep);
                return;
            }
        }

        // No branching target — continue to next sequential step
        $this->advanceToNextStep($delivery, $step);
    }

    /**
     * Evaluate conditions against delivery state.
     */
    protected function evaluateConditions(WhatsAppCampaignDelivery $delivery, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field    = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'eq';
            $value    = $condition['value'] ?? null;

            $actual = match ($field) {
                'delivery_status' => $delivery->status,
                'has_replied'     => $delivery->has_replied,
                'has_clicked'     => $delivery->has_clicked,
                'has_opted_out'   => $delivery->has_opted_out,
                default           => $delivery->metadata[$field] ?? null,
            };

            $matches = match ($operator) {
                'eq', '='    => $actual == $value,
                'neq', '!='  => $actual != $value,
                'is_true'    => (bool) $actual === true,
                'is_false'   => (bool) $actual === false,
                'in'         => in_array($actual, (array) $value),
                default      => false,
            };

            if (!$matches) return false;
        }

        return true;
    }

    /**
     * Update lead data as a step action.
     */
    protected function executeUpdateLead(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        if ($delivery->contactable_type === 'Modules\\CRM\\Models\\Lead' && $delivery->contactable_id) {
            $updates = $step->metadata['lead_updates'] ?? [];
            if (!empty($updates)) {
                \Modules\CRM\Models\Lead::withoutGlobalScopes()
                    ->where('id', $delivery->contactable_id)
                    ->update($updates);
            }
        }
    }

    /**
     * Add a tag to the lead.
     */
    protected function executeAddTag(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        if ($delivery->contactable_type === 'Modules\\CRM\\Models\\Lead' && $delivery->contactable_id) {
            $tagName = $step->metadata['tag_name'] ?? null;
            if ($tagName) {
                $lead = \Modules\CRM\Models\Lead::withoutGlobalScopes()->find($delivery->contactable_id);
                if ($lead) {
                    $tag = \Modules\CRM\Models\LeadTag::firstOrCreate([
                        'workspace_id' => $delivery->workspace_id,
                        'name'         => $tagName,
                    ]);
                    $lead->tags()->syncWithoutDetaching([$tag->id]);
                }
            }
        }
    }

    /**
     * Remove a tag from the lead.
     */
    protected function executeRemoveTag(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $step): void
    {
        if ($delivery->contactable_type === 'Modules\\CRM\\Models\\Lead' && $delivery->contactable_id) {
            $tagName = $step->metadata['tag_name'] ?? null;
            if ($tagName) {
                $lead = \Modules\CRM\Models\Lead::withoutGlobalScopes()->find($delivery->contactable_id);
                $tag = \Modules\CRM\Models\LeadTag::where('workspace_id', $delivery->workspace_id)
                    ->where('name', $tagName)->first();
                if ($lead && $tag) {
                    $lead->tags()->detach($tag->id);
                }
            }
        }
    }

    /**
     * Advance delivery to the next sequential step.
     */
    public function advanceToNextStep(WhatsAppCampaignDelivery $delivery, WhatsAppCampaignSequenceStep $currentStep): void
    {
        $nextStep = $currentStep->sequence->getNextStep($currentStep->step_order);

        if ($nextStep) {
            $this->scheduleStep($delivery, $nextStep);
        }
        // If no next step, the sequence is complete for this recipient
    }
}
