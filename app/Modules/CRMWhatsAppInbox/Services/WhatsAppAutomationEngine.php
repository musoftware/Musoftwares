<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use Modules\CRM\Models\WhatsAppAutomationRule;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use App\Modules\CRMWhatsAppInbox\Jobs\SendWhatsAppMessageJob;
use Illuminate\Support\Facades\Log;

class WhatsAppAutomationEngine
{
    public function __construct(
        protected MessageDeliveryService $messageService,
        protected ConversationAssignmentEngine $assignmentEngine,
    ) {}

    /**
     * Evaluate all automation rules for a given trigger event.
     */
    public function evaluate(WhatsAppConversation $conversation, string $triggerEvent, ?WhatsAppMessage $message = null): void
    {
        $rules = WhatsAppAutomationRule::withoutGlobalScopes()
            ->where('workspace_id', $conversation->workspace_id)
            ->where('is_active', true)
            ->where('trigger_event', $triggerEvent)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            try {
                if ($this->matchesConditions($rule, $conversation, $message)) {
                    $this->executeActions($rule, $conversation, $message);
                    $rule->incrementTriggerCount();
                }
            } catch (\Exception $e) {
                Log::error("Automation rule {$rule->id} failed: {$e->getMessage()}", [
                    'rule_id'         => $rule->id,
                    'conversation_id' => $conversation->id,
                    'trigger'         => $triggerEvent,
                ]);
            }
        }
    }

    /**
     * Check if all conditions for a rule match.
     */
    protected function matchesConditions(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation, ?WhatsAppMessage $message): bool
    {
        // Check business hours schedule
        if ($rule->type === 'business_hours' || $rule->type === 'away_message') {
            if ($rule->type === 'away_message' && $rule->isWithinSchedule()) {
                return false; // Away message only applies OUTSIDE business hours
            }
            if ($rule->type === 'business_hours' && !$rule->isWithinSchedule()) {
                return false;
            }
        }

        $conditions = $rule->conditions ?? [];

        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $conversation, $message)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition.
     */
    protected function evaluateCondition(array $condition, WhatsAppConversation $conversation, ?WhatsAppMessage $message): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        $actual = match ($field) {
            'conversation.type'     => $conversation->type,
            'conversation.status'   => $conversation->status,
            'conversation.priority' => $conversation->priority,
            'contact.phone'         => $conversation->contact_phone,
            'contact.name'          => $conversation->contact_name,
            'message.type'          => $message?->type,
            'message.body'          => $message?->body,
            'message.sender_type'   => $message?->sender_type,
            'is_first_message'      => $conversation->messages()->count() <= 1,
            'is_assigned'           => $conversation->isAssigned(),
            default                 => null,
        };

        return match ($operator) {
            'equals'       => $actual == $value,
            'not_equals'   => $actual != $value,
            'contains'     => is_string($actual) && str_contains(strtolower($actual), strtolower($value)),
            'not_contains' => is_string($actual) && !str_contains(strtolower($actual), strtolower($value)),
            'is_true'      => (bool) $actual === true,
            'is_false'     => (bool) $actual === false,
            'starts_with'  => is_string($actual) && str_starts_with(strtolower($actual), strtolower($value)),
            'regex'        => is_string($actual) && preg_match($value, $actual),
            default        => false,
        };
    }

    /**
     * Execute all actions for a matched rule.
     */
    protected function executeActions(WhatsAppAutomationRule $rule, WhatsAppConversation $conversation, ?WhatsAppMessage $message): void
    {
        $actions = $rule->actions ?? [];

        foreach ($actions as $action) {
            $this->executeAction($action, $rule, $conversation, $message);
        }
    }

    /**
     * Execute a single automation action.
     */
    protected function executeAction(array $action, WhatsAppAutomationRule $rule, WhatsAppConversation $conversation, ?WhatsAppMessage $message): void
    {
        $type = $action['type'] ?? '';

        match ($type) {
            'send_reply' => $this->actionSendReply($action, $conversation),
            'assign_agent' => $this->actionAssignAgent($action, $conversation),
            'assign_department' => $this->actionAssignDepartment($action, $conversation),
            'set_label' => $this->actionSetLabel($action, $conversation),
            'set_priority' => $this->actionSetPriority($action, $conversation),
            'set_type' => $this->actionSetType($action, $conversation),
            'add_note' => $this->actionAddNote($action, $conversation),
            default => Log::warning("Unknown automation action type: {$type}", ['rule_id' => $rule->id]),
        };
    }

    protected function actionSendReply(array $action, WhatsAppConversation $conversation): void
    {
        $body = $action['message'] ?? $action['body'] ?? '';
        if (!$body) return;

        // Replace placeholders
        $body = $this->replacePlaceholders($body, $conversation);

        $this->messageService->sendText(
            $conversation,
            $body,
            0, // System sender
        );
    }

    protected function actionAssignAgent(array $action, WhatsAppConversation $conversation): void
    {
        $strategy = $action['strategy'] ?? 'round_robin';
        $this->assignmentEngine->assign($conversation, $strategy);
    }

    protected function actionAssignDepartment(array $action, WhatsAppConversation $conversation): void
    {
        $department = $action['department'] ?? '';
        if (!$department) return;

        $conversation->update(['assigned_department' => $department]);
        $this->assignmentEngine->transfer($conversation, $department);
    }

    protected function actionSetLabel(array $action, WhatsAppConversation $conversation): void
    {
        $labelId = $action['label_id'] ?? null;
        if (!$labelId) return;

        $conversation->labels()->syncWithoutDetaching([$labelId]);
    }

    protected function actionSetPriority(array $action, WhatsAppConversation $conversation): void
    {
        $priority = $action['priority'] ?? 'medium';
        $conversation->update(['priority' => $priority]);
    }

    protected function actionSetType(array $action, WhatsAppConversation $conversation): void
    {
        $type = $action['conversation_type'] ?? 'general';
        $conversation->update(['type' => $type]);
    }

    protected function actionAddNote(array $action, WhatsAppConversation $conversation): void
    {
        $body = $action['note'] ?? '';
        if (!$body) return;

        $body = $this->replacePlaceholders($body, $conversation);
        $this->messageService->addInternalNote($conversation, "🤖 {$body}", 0);
    }

    /**
     * Replace message placeholders with conversation data.
     */
    protected function replacePlaceholders(string $text, WhatsAppConversation $conversation): string
    {
        return str_replace([
            '{{contact_name}}',
            '{{contact_phone}}',
            '{{agent_name}}',
            '{{conversation_id}}',
        ], [
            $conversation->contact_name ?? 'Customer',
            $conversation->contact_phone,
            $conversation->assignedAgent?->name ?? 'our team',
            $conversation->uuid,
        ], $text);
    }
}
