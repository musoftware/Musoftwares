<?php

return [
    // Navigation & Titles
    'title' => 'عروض الأسعار',
    'admin_title' => 'عروض الأسعار - الإدارة',
    'management_title' => 'عروض الأسعار والمقترحات العامة',
    'management_desc' => 'إنشاء وإدارة عروض الأسعار العامة، وتوليد روابط دفع الـ 50% للعملاء مع احتساب تلقائي للتكاليف الاسترشادية.',
    'create_new' => 'إنشاء عرض سعر جديد',
    'edit_title' => 'تعديل عرض السعر: :number',
    'show_title' => 'عرض سعر: :title - الإدارة',
    'public_link' => 'الرابط العام لعرض السعر',
    'shortlink' => 'الرابط المختصر',
    'view_details' => 'عرض التفاصيل',
    'duplicate' => 'استنساخ',
    'delete' => 'حذف',
    'save' => 'حفظ عرض السعر',
    'save_and_generate' => 'حفظ وتوليد الرابط المختصر',
    'save_changes' => 'حفظ التعديلات',
    'cancel' => 'إلغاء',
    'back_to_list' => 'العودة لعروض الأسعار',
    'back' => 'رجوع',

    // Statuses
    'status_all' => 'كافة الحالات',
    'status_active' => 'نشط',
    'status_draft' => 'مسودة',
    'status_archived' => 'مؤرشف',
    'active_badge' => 'نشط ومتاح للعملاء',

    // Currencies
    'currency_all' => 'كافة العملات',
    'currency_label' => 'العملة المعتمدة',

    // KPIs & Metrics
    'total_quotations' => 'إجمالي العروض',
    'active_quotations' => 'العروض النشطة',
    'active_quotations_count' => ':count عرض نشط وجاهز للإرسال',
    'total_views' => 'مشاهدات العروض',
    'total_views_desc' => 'إجمالي فتح العملاء للروابط العامة',
    'paid_orders' => 'العروض المدفوعة (50%)',
    'paid_orders_desc' => 'عملاء سددوا الدفعة المقدمة وبدأوا العمل',
    'total_collected' => 'الإيرادات المحصلة',
    'total_collected_desc' => 'إجمالي الدفعات المقدمة المحصلة',

    // Filters & Search
    'search_placeholder' => 'بحث بالعنوان، الرقم، أو الكلمات المفتاحية...',
    'search_button' => 'بحث',
    'all_records' => 'الإجمالي: :count عرض سعر',

    // Table & Columns
    'col_quotation' => 'عرض السعر',
    'col_development_total' => 'نطاق أعمالنا',
    'col_deposit_50' => 'الدفعة المقدمة 50%',
    'col_indicative_total' => 'تكاليف استرشادية',
    'col_grand_total' => 'الإجمالي التقديري',
    'col_views' => 'المشاهدات',
    'col_orders' => 'الطلبات المسددة',
    'col_status' => 'الحالة',
    'col_created' => 'تاريخ الإنشاء',
    'col_actions' => 'الإجراءات',
    'empty_state_title' => 'لا توجد عروض أسعار مسجلة',
    'empty_state_desc' => 'أنشئ أول عرض سعر لمشاركته مع عملائك المحتملين.',

    // Forms
    'section_basic' => 'المعلومات الأساسية والعملة',
    'section_basic_desc' => 'حدد عنوان العرض والعملة المعتمدة ونسبة الدفعة المقدمة المطلوبة لبدء المشروع.',
    'field_title' => 'عنوان العرض',
    'field_title_placeholder' => 'مثال: عرض سعر تطوير منصة تجارة إلكترونية متكاملة مع تطبيق موبايل',
    'field_deposit_percentage' => 'نسبة الدفعة المقدمة (%)',
    'field_deposit_percentage_desc' => 'النسبة التي يدفعها العميل من أعمال التطوير لبدء العمل (الافتراضي 50%).',
    'field_valid_until' => 'صلاحية العرض حتى (اختياري)',
    'field_status' => 'حالة العرض',
    'field_notes' => 'ملاحظات داخلية',
    'field_notes_placeholder' => 'ملاحظات داخلية خاصة بفريق العمل فقط...',

    // Items Manager
    'section_items' => 'جدول البنود والتسعير الهجين',
    'section_items_desc' => 'الدفعة المقدمة تحتسب فقط على بنود أعمال التطوير والتنفيذ الخاصة بنا.',
    'tab_our_work' => 'أعمال التطوير والبرمجة',
    'tab_indicative_cost' => 'التكاليف الاسترشادية الخارجية',
    'add_our_work_item' => 'إضافة بند عمل جديد',
    'add_indicative_item' => 'إضافة تكلفة استرشادية',
    'item_title' => 'اسم البند / الميزة',
    'item_title_placeholder' => 'مثال: تطوير لوحة التحكم ونظام الفواتير',
    'item_price' => 'السعر',
    'item_qty' => 'الكمية',
    'item_total' => 'الإجمالي',
    'item_description' => 'وصف تفصيلي للبند ومخرجاته (اختياري)...',
    'external_link' => 'رابط المزود الخارجي',
    'external_link_placeholder' => 'https://hetzner.com أو https://hostinger.com',
    'link_label' => 'نص الزر / اسم المزود',
    'link_label_placeholder' => 'مثال: حجز الاستضافة من Hetzner',

    // Financial Box
    'calc_summary_title' => 'ملخص الحسابات المالية التلقائية',
    'calc_dev_total' => 'إجمالي أعمالنا البرمجية',
    'calc_deposit' => 'الدفعة المقدمة (:pct%)',
    'calc_deposit_sub' => 'المبلغ المطلوب سداده لبدء العمل',
    'calc_indicative_total' => 'تكاليف استرشادية خارجية',
    'calc_indicative_sub' => 'يدفعها العميل لمزود الخدمة مباشرة',
    'calc_grand_total' => 'الإجمالي الشامل التقديري',

    // Markdown Scope Editor
    'section_scope' => 'نطاق العمل والشروط والمراحل التنفيذية',
    'section_scope_desc' => 'اكتب مواصفات المشروع، مراحل التنفيذ، والشروط باستخدام محرر Markdown مع معاينة حية.',
    'template_saas' => 'قالب SaaS',
    'template_ecommerce' => 'قالب متجر',
    'template_payment' => 'قالب شروط الدفع',

    // Show & Share Page
    'share_toolbox' => 'أدوات المشاركة والروابط',
    'copy_shortlink' => 'نسخ الرابط المختصر',
    'copy_public_link' => 'نسخ الرابط المباشر',
    'share_whatsapp' => 'مشاركة عبر واتساب',
    'preview_public' => 'معاينة العرض العام',
    'link_copied' => 'تم نسخ الرابط إلى الحافظة!',
    'wa_copied' => 'تم نسخ رسالة الواتساب الجاهزة!',
    'paid_orders_card' => 'دفعات العملاء والفواتير الصادرة',
    'client_name' => 'اسم العميل',
    'client_email' => 'البريد الإلكتروني',
    'client_phone' => 'الهاتف / الواتساب',
    'company' => 'الشركة',
    'paid_amount' => 'المبلغ المسدد',
    'paid_at' => 'تاريخ السداد',
    'view_invoice' => 'عرض الفاتورة',
    'no_orders_yet' => 'لا توجد دفعات مسددة مسجلة لهذا العرض حتى الآن.',

    // Notifications & Confirmations
    'delete_confirm' => 'هل أنت متأكد من حذف عرض السعر ":title"؟',
    'deleted_success' => 'تم حذف عرض السعر بنجاح.',
    'duplicated_success' => 'تم استنساخ عرض السعر بنجاح.',
    'created_success' => 'تم إنشاء عرض السعر بنجاح.',
    'updated_success' => 'تم تحديث عرض السعر بنجاح.',
];
