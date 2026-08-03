<?php

namespace App\Services\AI;

use App\Models\Project;
use Illuminate\Support\Str;

class ContextReconciler
{
    protected ScopePricingEngine $pricingEngine;

    public function __construct()
    {
        $this->pricingEngine = new ScopePricingEngine();
    }

    /**
     * Clean conversational noise from feature titles.
     * Example: "بقولك عايز Todo App" -> "Todo App"
     */
    public function cleanFeatureTitle(string $rawText): string
    {
        $text = strip_tags(trim($rawText));

        $fillers = [
            'بقولك عايز', 'بقولك عاوز', 'بقولك محتاج', 'بقولك انا عايز',
            'بقولك', 'عايز', 'عاوز', 'محتاج', 'مطلوب', 'ياريت', 'يا ريت',
            'انشئ', 'اعمل', 'زود', 'ضيف', 'نفسي في', 'i want', 'please add', 'need'
        ];

        foreach ($fillers as $filler) {
            if (mb_strpos(mb_strtolower($text), mb_strtolower($filler)) === 0) {
                $text = trim(mb_substr($text, mb_strlen($filler)));
            }
        }

        $cleaned = trim($text);
        return !empty($cleaned) ? mb_strimwidth($cleaned, 0, 50, '…') : mb_strimwidth($rawText, 0, 50, '…');
    }

    /**
     * Reconcile current project context against latest user text to detect contradictions.
     */
    public function reconcile(Project $project, string $userText): array
    {
        $context          = $project->ai_context ?? [];
        $previousType     = $context['current_archetype'] ?? null;
        $pendingFeatures  = $context['pending_features'] ?? [];

        // 1. Clean incoming feature title if user text contains feature/idea request
        $cleanedTitle = $this->cleanFeatureTitle($userText);
        
        $hasNewIdea = Str::contains(mb_strtolower($userText), [
            'todo', 'crud', 'متجر', 'موقع', 'تطبيق', 'داشبورد', 'crm', 'erp', 'هبوط', 'landing', 'app', 'system'
        ]);

        if ($hasNewIdea && !empty($cleanedTitle)) {
            if (!in_array($cleanedTitle, $pendingFeatures, true)) {
                $pendingFeatures[] = $cleanedTitle;
            }
        }

        // 2. Detect Archetype for latest user message
        $newArchetype = $this->pricingEngine->detectArchetype($userText . ' ' . implode(' ', $pendingFeatures));

        // 3. Conflict Detection & Scope Reconciliation
        $conflictDetected = false;
        $reconciliationReason = '';

        if (!empty($previousType) && $previousType !== $newArchetype) {
            // Scope conflict detected! User changed idea (e.g. from Corporate Website to Todo App)
            $conflictDetected = true;
            $reconciliationReason = "تم اكتشاف تعديل في نوع المشـروع من ({$previousType}) إلى ({$newArchetype}) بناءً على طلب العميل الصريح.";

            // Reset outdated features that belong to the previous archetype
            $pendingFeatures = [$cleanedTitle];
        }

        // Update context fields
        $context['current_archetype']      = $newArchetype;
        $context['pending_features']       = array_values(array_unique($pendingFeatures));
        $context['conflict_detected']      = $conflictDetected;
        $context['reconciliation_reason']  = $reconciliationReason;
        $context['last_user_message_clean'] = $cleanedTitle;

        $project->update(['ai_context' => $context]);

        return $context;
    }
}
