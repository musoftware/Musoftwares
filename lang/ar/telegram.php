<?php

// The site's Telegram bot: what a linked account sees there.
return [
    'welcome' => 'أهلاً :name، حسابك على Musoftware مربوط هنا. من الأزرار تحت تقدر تشوف فواتيرك ومشاريعك، أو تكتب أي سؤال والمساعد الذكي يرد عليك.',
    'not_linked' => 'أهلاً بيك في Musoftware. علشان تشوف فواتيرك ومشاريعك هنا، اربط حسابك من صفحة الإعدادات: :url',
    'btn_approvals' => 'الموافقات',
    'btn_invoices' => 'فواتيري',
    'btn_projects' => 'مشاريعي',
    'btn_support' => 'اسأل المساعد',
    'support_prompt' => 'اكتب سؤالك هنا، والمساعد الذكي هيرد عليك.',
    'invoices_title' => 'آخر فواتيرك:',
    'invoices_none' => 'مفيش فواتير على حسابك.',
    'invoice_line' => 'فاتورة #:id · :amount · :status',
    'invoice_status' => ['paid' => 'مدفوعة', 'unpaid' => 'مش مدفوعة', 'partially_paid' => 'مدفوعة جزئياً', 'cancelled' => 'ملغية', 'refunded' => 'مسترجعة'],
    'pay' => 'ادفع فاتورة #:id',
    'all_invoices' => 'كل الفواتير',
    'projects_title' => 'مشاريعك:',
    'projects_none' => 'مفيش مشاريع لسه. تقدر تبدأ مشروعك من هنا: :url',
    'project_line' => ':title · :status',
    'project_status' => ['chatting' => 'لسه في المحادثة', 'quoted' => 'السعر جاهز ومستني الدفع', 'queued' => 'اتدفع وفي الدور', 'designing' => 'التصميم شغال', 'building' => 'بيتنفّذ دلوقتي', 'ready' => 'جاهز', 'failed' => 'وقف، والفريق شغال عليه'],
    'untitled' => 'مشروع من غير اسم',
    'open' => 'افتح: :title',
];
