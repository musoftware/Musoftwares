<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_price_items', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_price_items', 'key')) {
                $table->string('key')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('contract_price_items', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('key');
            }
            if (!Schema::hasColumn('contract_price_items', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('contract_price_items', 'standalone_hours')) {
                $table->integer('standalone_hours')->default(4)->after('default_price');
            }
            if (!Schema::hasColumn('contract_price_items', 'marginal_hours')) {
                $table->integer('marginal_hours')->default(2)->after('standalone_hours');
            }
            if (!Schema::hasColumn('contract_price_items', 'complexity')) {
                $table->string('complexity')->default('medium')->after('marginal_hours');
            }
            if (!Schema::hasColumn('contract_price_items', 'keywords')) {
                $table->json('keywords')->nullable()->after('complexity');
            }
            if (!Schema::hasColumn('contract_price_items', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('keywords');
            }
            if (!Schema::hasColumn('contract_price_items', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Seed components into contract_price_items
        $defaultComponents = [
            [
                'key'              => 'authentication',
                'name'             => 'منظومة المصادقة وتسجيل الدخول (Auth)',
                'name_ar'          => 'منظومة المصادقة وتسجيل الدخول (Auth)',
                'name_en'          => 'Authentication System',
                'standalone_hours' => 4,
                'marginal_hours'   => 1,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['auth', 'تسجيل دخول', 'دخول', 'login', 'signup', 'مصادقة', 'انشاء حساب']),
                'sort_order'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'users',
                'name'             => 'إدارة المستخدمين والحسابات (Users)',
                'name_ar'          => 'إدارة المستخدمين والحسابات (Users)',
                'name_en'          => 'User Management',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => json_encode(['users', 'مستخدمين', 'حسابات', 'عملاء', 'اعضاء']),
                'sort_order'       => 2,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'roles_permissions',
                'name'             => 'الأدوار والصلاحيات (Roles & Permissions)',
                'name_ar'          => 'الأدوار والصلاحيات (Roles & Permissions)',
                'name_en'          => 'Roles & Permissions',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['roles', 'permissions', 'صلاحيات', 'أدوار', 'تراخيص']),
                'sort_order'       => 3,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'profile',
                'name'             => 'الملف الشخصي والإعدادات (Profile)',
                'name_ar'          => 'الملف الشخصي والإعدادات (Profile)',
                'name_en'          => 'User Profile Management',
                'standalone_hours' => 3,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => json_encode(['profile', 'ملف شخصي', 'حسابي', 'تعديل البيانات']),
                'sort_order'       => 4,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'dashboard',
                'name'             => 'لوحة التحكم المركزية (Dashboard)',
                'name_ar'          => 'لوحة التحكم المركزية (Dashboard)',
                'name_en'          => 'Admin / Overview Dashboard',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['dashboard', 'داشبورد', 'لوحة تحكم', 'رئيسية']),
                'sort_order'       => 5,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'crud',
                'name'             => 'إدارة البيانات والجداول القياسية (CRUD)',
                'name_ar'          => 'إدارة البيانات والجداول القياسية (CRUD)',
                'name_en'          => 'Standard Data CRUD',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => json_encode(['crud', 'إضافة وتعديل', 'إدارة بيانات', 'قائمة']),
                'sort_order'       => 6,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'search_filter',
                'name'             => 'محرك البحث والتصفية المتقدمة (Search & Filter)',
                'name_ar'          => 'محرك البحث والتصفية المتقدمة (Search & Filter)',
                'name_en'          => 'Search & Advanced Filtering',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => json_encode(['search', 'filter', 'بحث', 'فلترة', 'تصفية']),
                'sort_order'       => 7,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'file_manager',
                'name'             => 'مدير الملفات والرفع (File & Media Manager)',
                'name_ar'          => 'مدير الملفات والرفع (File & Media Manager)',
                'name_en'          => 'File & Media Management',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['files', 'media', 'ملفات', 'مرفقات', 'رفع صور', 'وسائط']),
                'sort_order'       => 8,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'tasks_todo',
                'name'             => 'إدارة المهام والواجبات (Tasks / Todo)',
                'name_ar'          => 'إدارة المهام والواجبات (Tasks / Todo)',
                'name_en'          => 'Task & Todo Management',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['todo', 'tasks', 'مهام', 'قائمة مهام', 'واجبات']),
                'sort_order'       => 9,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'crm_leads',
                'name'             => 'إدارة المبيعات والعملاء المحتملين (CRM Pipeline)',
                'name_ar'          => 'إدارة المبيعات والعملاء المحتملين (CRM Pipeline)',
                'name_en'          => 'CRM Leads & Sales Pipeline',
                'standalone_hours' => 12,
                'marginal_hours'   => 6,
                'complexity'       => 'high',
                'keywords'         => json_encode(['crm', 'leads', 'مبيعات', 'عملاء محتملين', 'صفقات']),
                'sort_order'       => 10,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'invoices_billing',
                'name'             => 'الفواتير والمالية (Invoices & Billing)',
                'name_ar'          => 'الفواتير والمالية (Invoices & Billing)',
                'name_en'          => 'Invoices & Financial Billing',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['invoices', 'billing', 'فواتير', 'حسابات', 'مالية', 'دفعات']),
                'sort_order'       => 11,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'payments',
                'name'             => 'بوابات الدفع الإلكتروني (Payment Gateways)',
                'name_ar'          => 'بوابات الدفع الإلكتروني (Payment Gateways)',
                'name_en'          => 'Online Payment Integration (Stripe/Paymob)',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['stripe', 'paypal', 'paymob', 'payment', 'دفع', 'فيزا', 'بوابة دفع']),
                'sort_order'       => 12,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'reports_analytics',
                'name'             => 'التقارير والإحصائيات (Reports & Analytics)',
                'name_ar'          => 'التقارير والإحصائيات (Reports & Analytics)',
                'name_en'          => 'Advanced Reports & Analytics',
                'standalone_hours' => 10,
                'marginal_hours'   => 5,
                'complexity'       => 'high',
                'keywords'         => json_encode(['reports', 'analytics', 'تقارير', 'إحصائيات', 'رسوم بيانية', 'pdf']),
                'sort_order'       => 13,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'calendar_scheduling',
                'name'             => 'التقويم والمواعيد (Calendar & Scheduling)',
                'name_ar'          => 'التقويم والمواعيد (Calendar & Scheduling)',
                'name_en'          => 'Calendar & Appointments',
                'standalone_hours' => 8,
                'marginal_hours'   => 4,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['calendar', 'scheduling', 'تقويم', 'مواعيد', 'حجوزات']),
                'sort_order'       => 14,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'notifications',
                'name'             => 'منظومة الإشعارات (Notifications)',
                'name_ar'          => 'منظومة الإشعارات (Notifications)',
                'name_en'          => 'Notification Engine',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['notifications', 'إشعارات', 'تنبيهات', 'push']),
                'sort_order'       => 15,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'whatsapp_sms',
                'name'             => 'ربط الرسائل والواتساب (WhatsApp / SMS API)',
                'name_ar'          => 'ربط الرسائل والواتساب (WhatsApp / SMS API)',
                'name_en'          => 'WhatsApp & SMS Gateway Integration',
                'standalone_hours' => 6,
                'marginal_hours'   => 3,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['whatsapp', 'sms', 'واتساب', 'رسائل قصيرة', 'تواصل']),
                'sort_order'       => 16,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'chat_messaging',
                'name'             => 'المحادثات والدردشة المباشرة (Live Chat / Messaging)',
                'name_ar'          => 'المحادثات والدردشة المباشرة (Live Chat / Messaging)',
                'name_en'          => 'Real-time Chat & Messaging',
                'standalone_hours' => 12,
                'marginal_hours'   => 6,
                'complexity'       => 'high',
                'keywords'         => json_encode(['chat', 'messaging', 'دردشة', 'محادثات', 'تواصل مباشر']),
                'sort_order'       => 17,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'ai_agent',
                'name'             => 'المساعد الذكي ووظائف AI (AI Agent & Automation)',
                'name_ar'          => 'المساعد الذكي ووظائف AI (AI Agent & Automation)',
                'name_en'          => 'AI Agent & Intelligent Workflows',
                'standalone_hours' => 14,
                'marginal_hours'   => 8,
                'complexity'       => 'high',
                'keywords'         => json_encode(['ai', 'agent', 'ذكاء اصطناعي', 'بوت', 'bot', 'openai', 'gemini']),
                'sort_order'       => 18,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'workflow_approval',
                'name'             => 'دورات العمل والاعتمادات (Workflow & Approvals)',
                'name_ar'          => 'دورات العمل والاعتمادات (Workflow & Approvals)',
                'name_en'          => 'Workflow Engine & Approvals',
                'standalone_hours' => 10,
                'marginal_hours'   => 5,
                'complexity'       => 'high',
                'keywords'         => json_encode(['workflow', 'approval', 'اعتمادات', 'مسار عمل', 'موافقات']),
                'sort_order'       => 19,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'audit_logs',
                'name'             => 'سجل العمليات والتغييرات (Audit Trail)',
                'name_ar'          => 'سجل العمليات والتغييرات (Audit Trail)',
                'name_en'          => 'Audit Logs & Security Trail',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'low',
                'keywords'         => json_encode(['audit', 'logs', 'سجل', 'مراقبة التغييرات', 'تتبع']),
                'sort_order'       => 20,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'bug_fix',
                'name'             => 'إصلاح مشكلة / خطأ برمجي (Bug Fix)',
                'name_ar'          => 'إصلاح مشكلة / خطأ برمجي (Bug Fix)',
                'name_en'          => 'Bug Fix / Issue Resolution',
                'standalone_hours' => 1,
                'marginal_hours'   => 1,
                'complexity'       => 'low',
                'keywords'         => json_encode(['bug', 'fix', 'مشكلة', 'إصلاح', 'خطأ', 'تعديل بسيط']),
                'sort_order'       => 21,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'enhancement',
                'name'             => 'تطوير وتحسين ميزة قائمة (Enhancement)',
                'name_ar'          => 'تطوير وتحسين ميزة قائمة (Enhancement)',
                'name_en'          => 'Feature Enhancement',
                'standalone_hours' => 3,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => json_encode(['enhancement', 'تحسين', 'تطوير ميزة', 'تحديث']),
                'sort_order'       => 22,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'key'              => 'generic_feature',
                'name'             => 'وحدة برمجية مخصصة (Custom Feature Unit)',
                'name_ar'          => 'وحدة برمجية مخصصة (Custom Feature Unit)',
                'name_en'          => 'Custom Feature Unit',
                'standalone_hours' => 4,
                'marginal_hours'   => 2,
                'complexity'       => 'medium',
                'keywords'         => json_encode([]),
                'sort_order'       => 23,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ];

        foreach ($defaultComponents as $comp) {
            DB::table('contract_price_items')->updateOrInsert(
                ['key' => $comp['key']],
                $comp
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_price_items', function (Blueprint $table) {
            $table->dropColumn([
                'key',
                'name_ar',
                'name_en',
                'standalone_hours',
                'marginal_hours',
                'complexity',
                'keywords',
                'is_active',
                'sort_order',
            ]);
        });
    }
};
