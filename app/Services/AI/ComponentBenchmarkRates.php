<?php

namespace App\Services\AI;

use App\Models\ContractPriceItem;

class ComponentBenchmarkRates
{
    /**
     * Standard Software Component Reference Map.
     * Fetches dynamic software components from ContractPriceItem database table, with fallback to default array.
     */
    public static function getComponents(): array
    {
        try {
            $dbComponents = ContractPriceItem::whereNotNull('key')
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->get();

            if ($dbComponents->count() > 0) {
                $mapped = [];
                foreach ($dbComponents as $comp) {
                    $key = $comp->key ?: strtolower(str_replace(' ', '_', $comp->name));
                    $mapped[$key] = [
                        'key'              => $key,
                        'name_ar'          => $comp->name_ar ?: $comp->name,
                        'name_en'          => $comp->name_en ?: $comp->name,
                        'standalone_hours' => $comp->standalone_hours ?: 4,
                        'marginal_hours'   => $comp->marginal_hours ?: 2,
                        'complexity'       => $comp->complexity ?: 'medium',
                        'keywords'         => is_array($comp->keywords) ? $comp->keywords : json_decode($comp->keywords ?? '[]', true),
                    ];
                }
                return $mapped;
            }
        } catch (\Throwable $e) {
            // DB offline or table missing fallback
        }

        return self::getDefaultComponents();
    }

    /**
     * Default fallback component registry array.
     */
    public static function getDefaultComponents(): array
    {
        return [
            'authentication' => [
                'name_ar'          => 'منظومة المصادقة وتسجيل الدخول (Auth)',
                'name_en'          => 'Authentication System',
                'standalone_hours' => 4,
                'marginal_hours'   => 1,
                'complexity'       => 'medium',
                'keywords'         => ['auth', 'تسجيل دخول', 'دخول', 'login', 'signup', 'مصادقة', 'انشاء حساب'],
            ],
            'users' => [
                'name_ar'          => 'إدارة المستخدمين والحسابات (Users)',
                'name_en'          => 'User Management',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => ['users', 'مستخدمين', 'حسابات', 'عملاء', 'اعضاء'],
            ],
            'roles_permissions' => [
                'name_ar'          => 'الأدوار والصلاحيات (Roles & Permissions)',
                'name_en'          => 'Roles & Permissions',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['roles', 'permissions', 'صلاحيات', 'أدوار', 'تراخيص'],
            ],
            'profile' => [
                'name_ar'          => 'الملف الشخصي والإعدادات (Profile)',
                'name_en'          => 'User Profile Management',
                'standalone_hours' => 3,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => ['profile', 'ملف شخصي', 'حسابي', 'تعديل البيانات'],
            ],
            'dashboard' => [
                'name_ar'          => 'لوحة التحكم المركزية (Dashboard)',
                'name_en'          => 'Admin / Overview Dashboard',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['dashboard', 'داشبورد', 'لوحة تحكم', 'رئيسية'],
            ],
            'crud' => [
                'name_ar'          => 'إدارة البيانات والجداول القياسية (CRUD)',
                'name_en'          => 'Standard Data CRUD',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => ['crud', 'إضافة وتعديل', 'إدارة بيانات', 'قائمة'],
            ],
            'search_filter' => [
                'name_ar'          => 'محرك البحث والتصفية المتقدمة (Search & Filter)',
                'name_en'          => 'Search & Advanced Filtering',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => ['search', 'filter', 'بحث', 'فلترة', 'تصفية'],
            ],
            'file_manager' => [
                'name_ar'          => 'مدير الملفات والرفع (File & Media Manager)',
                'name_en'          => 'File & Media Management',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['files', 'media', 'ملفات', 'مرفقات', 'رفع صور', 'وسائط'],
            ],
            'tasks_todo' => [
                'name_ar'          => 'إدارة المهام والواجبات (Tasks / Todo)',
                'name_en'          => 'Task & Todo Management',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['todo', 'tasks', 'مهام', 'قائمة مهام', 'واجبات'],
            ],
            'crm_leads' => [
                'name_ar'          => 'إدارة المبيعات والعملاء المحتملين (CRM Pipeline)',
                'name_en'          => 'CRM Leads & Sales Pipeline',
                'standalone_hours' => 12,
                'marginal_hours'   => 6,
                'complexity'       => 'high',
                'keywords'         => ['crm', 'leads', 'مبيعات', 'عملاء محتملين', 'صفقات'],
            ],
            'invoices_billing' => [
                'name_ar'          => 'الفواتير والمالية (Invoices & Billing)',
                'name_en'          => 'Invoices & Financial Billing',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => ['invoices', 'billing', 'فواتير', 'حسابات', 'مالية', 'دفعات'],
            ],
            'payments' => [
                'name_ar'          => 'بوابات الدفع الإلكتروني (Payment Gateways)',
                'name_en'          => 'Online Payment Integration (Stripe/Paymob)',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => ['stripe', 'paypal', 'paymob', 'payment', 'دفع', 'فيزا', 'بوابة دفع'],
            ],
            'reports_analytics' => [
                'name_ar'          => 'التقارير والإحصائيات (Reports & Analytics)',
                'name_en'          => 'Advanced Reports & Analytics',
                'standalone_hours' => 10,
                'marginal_hours'   => 5,
                'complexity'       => 'high',
                'keywords'         => ['reports', 'analytics', 'تقارير', 'إحصائيات', 'رسوم بيانية', 'pdf'],
            ],
            'calendar_scheduling' => [
                'name_ar'          => 'التقويم والمواعيد (Calendar & Scheduling)',
                'name_en'          => 'Calendar & Appointments',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => ['calendar', 'scheduling', 'تقويم', 'مواعيد', 'حجوزات'],
            ],
            'notifications' => [
                'name_ar'          => 'منظومة الإشعارات (Notifications)',
                'name_en'          => 'Notification Engine',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['notifications', 'إشعارات', 'تنبيهات', 'push'],
            ],
            'whatsapp_sms' => [
                'name_ar'          => 'ربط الرسائل والواتساب (WhatsApp / SMS API)',
                'name_en'          => 'WhatsApp & SMS Gateway Integration',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => ['whatsapp', 'sms', 'واتساب', 'رسائل قصيرة', 'تواصل'],
            ],
            'whatsapp_channels' => [
                'name_ar'          => 'ربط وتكامل قنوات الواتساب (WhatsApp Channels)',
                'name_en'          => 'WhatsApp Channels Integration',
                'standalone_hours' => 5,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['whatsapp channels', 'قنوات واتساب', 'قنوات الواتساب', 'whatsapp channel', 'واتساب تشانل'],
            ],
            'telegram_bot' => [
                'name_ar'          => 'إدارة وتطوير بوتات التلجرام (Telegram Bot Management)',
                'name_en'          => 'Telegram Bot Management',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['telegram bot', 'بوت تلجرام', 'بوتات تلجرام', 'ادارة بوت تلجرام', 'telegram'],
            ],
            'telegram_channel_collector' => [
                'name_ar'          => 'استخراج وجمع بيانات قنوات التلجرام (Telegram Channel Collection)',
                'name_en'          => 'Telegram Channel Collection',
                'standalone_hours' => 3,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['telegram collection', 'جمع قنوات تلجرام', 'سحب تلجرام', 'استخراج تلجرام', 'telegram channel scraper'],
            ],
            'whatsapp_channel_collector' => [
                'name_ar'          => 'استخراج وجمع بيانات قنوات الواتساب (WhatsApp Channel Collection)',
                'name_en'          => 'WhatsApp Channel Collection',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['whatsapp collection', 'جمع قنوات واتساب', 'سحب واتساب', 'استخراج واتساب', 'whatsapp scraper'],
            ],
            'auto_data_jobs' => [
                'name_ar'          => 'مهام الجمع والأتمتة المجدولة للبيانات (Automated Data Collection Jobs)',
                'name_en'          => 'Automated Data Collection Jobs',
                'standalone_hours' => 5,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['data jobs', 'مهام مجدولة', 'جمع بيانات تلقائي', 'cron jobs', 'سحب بيانات', 'automation jobs'],
            ],
            'content_deduplication' => [
                'name_ar'          => 'معالجة وتنقية وتصفية المحتوى ومنع التكرار (Content Deduplication & Processing)',
                'name_en'          => 'Content Deduplication & Processing',
                'standalone_hours' => 3,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => ['deduplication', 'منع التكرار', 'تنقية المحتوى', 'معالجة النصوص', 'فلترة البيانات', 'تصفية المحتوى'],
            ],
            'chat_messaging' => [
                'name_ar'          => 'المحادثات والدردشة المباشرة (Live Chat / Messaging)',
                'name_en'          => 'Real-time Chat & Messaging',
                'standalone_hours' => 12,
                'marginal_hours'   => 6,
                'complexity'       => 'high',
                'keywords'         => ['chat', 'messaging', 'دردشة', 'محادثات', 'تواصل مباشر'],
            ],
            'ai_agent' => [
                'name_ar'          => 'المساعد الذكي ووظائف AI (AI Agent & Automation)',
                'name_en'          => 'AI Agent & Intelligent Workflows',
                'standalone_hours' => 14,
                'marginal_hours'   => 8,
                'complexity'       => 'high',
                'keywords'         => ['ai', 'agent', 'ذكاء اصطناعي', 'openai', 'gemini', 'chatgpt', 'llm', 'الذكاء الاصطناعي'],
            ],
            'workflow_approval' => [
                'name_ar'          => 'دورات العمل والاعتمادات (Workflow & Approvals)',
                'name_en'          => 'Workflow Engine & Approvals',
                'standalone_hours' => 10,
                'marginal_hours'   => 5,
                'complexity'       => 'high',
                'keywords'         => ['workflow', 'approval', 'اعتمادات', 'مسار عمل', 'موافقات'],
            ],
            'audit_logs' => [
                'name_ar'          => 'سجل العمليات والتغييرات (Audit Trail)',
                'name_en'          => 'Audit Logs & Security Trail',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => ['audit', 'logs', 'سجل', 'مراقبة التغييرات', 'تتبع'],
            ],
            'bug_fix' => [
                'name_ar'          => 'إصلاح مشكلة / خطأ برمجي (Bug Fix)',
                'name_en'          => 'Bug Fix / Issue Resolution',
                'standalone_hours' => 1,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => ['bug', 'fix', 'مشكلة', 'إصلاح', 'خطأ', 'تعديل بسيط'],
            ],
            'enhancement' => [
                'name_ar'          => 'تطوير وتحسين ميزة قائمة (Enhancement)',
                'name_en'          => 'Feature Enhancement',
                'standalone_hours' => 3,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => ['enhancement', 'تحسين', 'تطوير ميزة', 'تحديث'],
            ],
            'upload_google_play' => [
                'name_ar'          => 'رفع التطبيق على متجر جوجل بلاي (Upload APK/AAB to Google Play)',
                'name_en'          => 'Upload APK/AAB to Google Play Store',
                'standalone_hours' => 1,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => ['google play', 'apk', 'aab', 'جوجل بلاي', 'رفع جوجل بلاي', 'play store', 'upload apk', 'upload aab', 'رفع تطبيق اندرويد', 'متجر بلاي', 'متجر جوجل', 'نشر على جوجل بلاي', 'رفع التطبيقات'],
            ],
            'upload_apple_store' => [
                'name_ar'          => 'رفع التطبيق على متجر آب ستور (Upload IPA to Apple Store)',
                'name_en'          => 'Upload IPA/iOS to Apple App Store',
                'standalone_hours' => 1,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => ['apple store', 'app store', 'ipa', 'ips', 'ios', 'آب ستور', 'ابل ستور', 'رفع ابل ستور', 'رفع آب ستور', 'نشر تطبيق ايفون', 'upload ipa', 'upload ips', 'متجر ابل', 'متجر أبل', 'testflight', 'تست فلايت', 'نشر على آب ستور', 'رفع التطبيقات'],
            ],
            'generic_feature' => [
                'name_ar'          => 'وحدة برمجية مخصصة (Custom Feature Unit)',
                'name_en'          => 'Custom Feature Unit',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => [],
            ],
        ];
    }

    /**
     * Resolve a component key or input text to a structured component definition.
     */
    public static function resolveComponent(string|array $input): array
    {
        $componentsMap = self::getComponents();

        if (is_array($input)) {
            $rawName = $input['name'] ?? $input['key'] ?? $input['title'] ?? 'generic_feature';
            $complexity = strtolower($input['complexity'] ?? '');
        } else {
            $rawName = (string) $input;
            $complexity = '';
        }

        $normalizedKey = strtolower(trim($rawName));

        // Direct key match
        if (isset($componentsMap[$normalizedKey])) {
            $comp = $componentsMap[$normalizedKey];
            if (!empty($complexity) && in_array($complexity, ['low', 'medium', 'high'])) {
                $comp['complexity'] = $complexity;
            }
            return $comp;
        }

        // Fuzzy match by keywords
        foreach ($componentsMap as $key => $comp) {
            $keywords = is_array($comp['keywords']) ? $comp['keywords'] : [];
            foreach ($keywords as $kw) {
                if (!empty($kw) && str_contains($normalizedKey, strtolower($kw))) {
                    if (!empty($complexity) && in_array($complexity, ['low', 'medium', 'high'])) {
                        $comp['complexity'] = $complexity;
                    }
                    return $comp;
                }
            }
        }

        // Fallback for custom component
        $hours = match ($complexity) {
            'low' => 2,
            'high' => 8,
            default => 4,
        };

        return [
            'name_ar'          => $rawName,
            'name_en'          => $rawName,
            'standalone_hours' => $hours,
            'marginal_hours'   => max(1, (int) round($hours * 0.5)),
            'complexity'       => !empty($complexity) ? $complexity : 'medium',
            'keywords'         => [],
        ];
    }
}
