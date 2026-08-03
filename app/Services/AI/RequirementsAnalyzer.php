<?php

namespace App\Services\AI;

use App\Models\Project;
use Illuminate\Support\Str;

class RequirementsAnalyzer
{
    /**
     * Essential project criteria checklist to determine completeness.
     */
    private const CRITICAL_CRITERIA = [
        'project_type'   => ['موقع', 'متجر', 'تطبيق', 'نظام', 'صفحة', 'داشبورد', 'crm', 'erp', 'app', 'website', 'store'],
        'core_features'  => ['ميزة', 'خاصية', 'دفع', 'تسجيل', 'إدارة', 'إشعارات', 'سلة', 'منتجات', 'حسابات', 'feature', 'login', 'payment'],
        'target_audience'=> ['عملاء', 'مستخدمين', 'شركات', 'أفراد', 'سعودية', 'مصر', 'أطباء', 'طلاب', 'users', 'clients', 'target'],
        'integrations'   => ['بوابة دفع', 'واتساب', 'رسائل', 'خرائط', 'api', 'stripe', 'paymob', 'whatsapp'],
    ];

    /**
     * Analyze requirements completeness and identify missing details.
     */
    public function analyze(Project $project, string $userText, array $parsedIntent = []): array
    {
        $context          = $project->ai_context ?? [];
        $pendingFeatures  = $context['pending_features'] ?? [];
        $completedFeatures = $context['completed_features'] ?? [];
        $rawFeatures = array_merge($pendingFeatures, $completedFeatures);
        $allFeatures = array_values(array_unique(array_map(function ($f) {
            if (is_array($f)) {
                return $f['title'] ?? $f['name'] ?? json_encode($f, JSON_UNESCAPED_UNICODE);
            }
            return (string) $f;
        }, $rawFeatures), SORT_REGULAR));

        $fullText = mb_strtolower($userText . ' ' . implode(' ', $allFeatures) . ' ' . ($project->project_name ?? '') . ' ' . ($project->description ?? ''));

        $detectedCount = 0;
        $missingInfo   = [];

        foreach (self::CRITICAL_CRITERIA as $key => $keywords) {
            $found = false;
            foreach ($keywords as $kw) {
                if (str_contains($fullText, $kw)) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $detectedCount++;
            } else {
                $missingInfo[] = match ($key) {
                    'project_type'    => 'تحديد نوع التطبيق أو النظام (موقع/متجر/موبايل/داشبورد)',
                    'core_features'   => 'تحديد الخصائص الفنية الرئيسية',
                    'target_audience' => 'الفئة المستهدفة أو النطاق الجغرافي',
                    'integrations'    => 'بوابات الدفع أو الربط الخارجي المطلوب',
                };
            }
        }

        $completenessScore = min(100, round(($detectedCount / count(self::CRITICAL_CRITERIA)) * 100));

        // Evaluate whether this message is a NEW requirement or a MODIFICATION
        $isScopeAddition = Str::contains($fullText, ['إضافة', 'ميزة جديدة', 'أيضاً', 'ايضا', 'زود', 'كمان', 'add', 'new feature']);
        $isModification  = Str::contains($fullText, ['تعديل', 'تغيير', 'بدل', 'عوضاً', 'modify', 'change']);

        return [
            'is_complete'            => $completenessScore >= 75 && count($allFeatures) > 0,
            'completeness_score'     => (int) $completenessScore,
            'missing_information'    => array_values($missingInfo),
            'questions_needed_count' => count($missingInfo),
            'stop_asking_questions'  => $completenessScore >= 75 || count($missingInfo) <= 1,
            'is_scope_addition'      => $isScopeAddition,
            'is_modification'        => $isModification,
            'total_features_count'   => count($allFeatures),
        ];
    }
}
