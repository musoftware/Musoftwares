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
     * Clean conversational noise from feature/component titles.
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
     * Reconcile current project context against latest user text to sync component features.
     */
    public function reconcile(Project $project, string $userText): array
    {
        $context         = $project->ai_context ?? [];
        $pendingFeatures = $context['pending_features'] ?? [];

        // Clean incoming feature/component title
        $cleanedTitle = $this->cleanFeatureTitle($userText);

        $hasNewIdea = Str::contains(mb_strtolower($userText), [
            'todo', 'crud', 'متجر', 'موقع', 'تطبيق', 'داشبورد', 'crm', 'erp', 'هبوط', 'landing', 'app', 'system', 'auth', 'دخول', 'شاشة'
        ]);

        if ($hasNewIdea && !empty($cleanedTitle)) {
            if (!in_array($cleanedTitle, $pendingFeatures, true)) {
                $pendingFeatures[] = $cleanedTitle;
            }
        }

        // Update context fields
        $context['pending_features']       = array_values(array_unique($pendingFeatures, SORT_REGULAR));
        $context['conflict_detected']      = false;
        $context['reconciliation_reason']  = '';
        $context['last_user_message_clean'] = $cleanedTitle;

        $project->update(['ai_context' => $context]);

        return $context;
    }
}
