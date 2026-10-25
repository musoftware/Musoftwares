<?php

// نصوص صفحة الإعداد في لوحة الأدمن (App\Services\Admin\SetupChecklist).
return [
    'title' => 'الإعداد',
    'intro' => 'كل الحاجات اللي لسه محتاجة منك خطوة. البنود اللي فيها مربع اختيار بتتعمل برا الموقع، فعلّم عليها بنفسك لما تخلصها.',
    'all_done' => 'كله تمام. مفيش حاجة محتاجة منك خطوة دلوقتي.',
    'progress' => 'خلصت :done من :total',
    'open' => 'افتح',
    'mark_done' => 'علّم إنها اتعملت',
    'mark_not_done' => 'رجّعها لسه ما اتعملتش',

    'group_site' => 'إعدادات الموقع',
    'group_seo' => 'جوجل ومحركات البحث',
    'group_queue' => 'مستني منك رد',

    'migrations' => 'شغّل تحديثات قاعدة البيانات المتأخرة',
    'migrations_note' => 'فيه :count تحديث مستني. خد نسخة احتياطية الأول، وبعدين شغّلهم.',
    'analytics' => 'ضيف Google Analytics أو Google Tag Manager',
    'analytics_note' => 'من غيره مش هتعرف عدد الزوار ولا أنهي صفحات بتجيب عملاء.',
    'payments' => 'حط مفاتيح الدفع الحقيقية بتاعة Kashier',
    'payments_note' => 'اكتب KASHIER_MERCHANT_ID و KASHIER_SECRET في ملف .env على السيرفر. الموقع شغال بقيم تجريبية دلوقتي.',
    'mail' => 'ظبط إرسال الإيميلات',
    'mail_note' => 'قيمة MAIL_MAILER في .env هي ":mailer"، يعني مفيش إيميل بيطلع من الموقع. استخدم smtp أو خدمة إرسال حقيقية.',
    'product_screenshots' => 'ضيف صور لكل برنامج في المتجر',
    'product_screenshots_note' => 'من غير صور: :names',

    'search_console_sitemap' => 'ابعت خريطة الموقع لـ Google Search Console',
    'search_console_sitemap_note' => 'Search Console ثم Sitemaps وضيف :url. وكرّرها بعد أي تغيير كبير في الموقع.',
    'search_console_reindex' => 'اطلب من جوجل يقرا الصفحات الرئيسية تاني',
    'search_console_reindex_note' => 'Search Console ثم URL inspection ثم Request indexing، للصفحة الرئيسية و /services، بالإنجليزي ومع ?lang=ar.',
    'bing_webmaster' => 'ضيف الموقع في Bing Webmaster Tools',
    'bing_webmaster_note' => 'تقدر تستورده من Search Console بضغطة واحدة، وبعدين ابعت نفس خريطة الموقع. Bing كمان بيغذي بحث ChatGPT.',
    'google_business_profile' => 'اعمل Google Business Profile',
    'google_business_profile_note' => 'ضيف عنوان السويس والتليفون ومواعيد الشغل ورابط الموقع، علشان تظهر على خرائط جوجل.',

    'open_tickets' => 'رد على تذاكر الدعم المفتوحة',
    'open_tickets_note' => 'فيه :count تذكرة مستنية.',
    'pending_services' => 'راجع خدمات الماركت بليس اللي مستنية موافقة',
    'pending_services_note' => 'فيه :count خدمة مستنية.',
    'withdrawals' => 'راجع طلبات السحب',
    'withdrawals_note' => 'فيه :count طلب مستني.',
];
