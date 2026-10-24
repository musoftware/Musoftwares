<?php

/**
 * Topic queue for the weekly blog draft. One topic is used per run, in this order, and a topic is never used twice.
 * Every topic is about one of our own products and a question a buyer in Egypt or the Gulf really asks.
 * 'product' is the slug of the /services page the article must link to.
 */
return [
    ['key' => 'clinic-appointments-no-shows', 'product' => 'dental-clinic-system',
        'ar' => 'إزاي تنظم حجوزات العيادة وتقلل الغياب عن المواعيد', 'en' => 'How to organize clinic appointments and reduce no-shows'],
    ['key' => 'restaurant-kitchen-display', 'product' => 'restaurant-pos-system',
        'ar' => 'هل مطعمك أو الكافيه بتاعك محتاج شاشة مطبخ ونظام طلبات على الترابيزات', 'en' => 'Does your restaurant need a kitchen display and table orders'],
    ['key' => 'supermarket-barcode-scale', 'product' => 'supermarket-system',
        'ar' => 'كاشير سوبر ماركت بالباركود والأصناف بالوزن: إيه اللي تدور عليه قبل ما تشتري', 'en' => 'Supermarket POS with barcodes and weighed items: what to look for before you buy'],
    ['key' => 'tutoring-center-fees', 'product' => 'education-center-system',
        'ar' => 'إدارة مصاريف السناتر ومجموعات الدروس بدل الدفتر الورقي', 'en' => 'Managing tutoring center fees and groups instead of a paper notebook'],
    ['key' => 'nursery-daily-report', 'product' => 'nursery-system',
        'ar' => 'التقرير اليومي للحضانة وتواصل أولياء الأمور بشكل منظم وآمن', 'en' => 'The nursery daily report and organized, safe communication with parents'],
    ['key' => 'lab-results-delivery', 'product' => 'lab-system',
        'ar' => 'تسليم نتايج المعمل للمريض بطريقة منظمة وآمنة', 'en' => 'Delivering lab results to patients in an organized and safe way'],
    ['key' => 'real-estate-rent-follow-up', 'product' => 'real-estate-system',
        'ar' => 'متابعة إيجارات وعقود مكتب العقارات من غير ما حاجة تضيع', 'en' => 'Following up rents and contracts in a real estate office without losing track'],
    ['key' => 'business-email-finder-rules', 'product' => 'email-finder-pro',
        'ar' => 'إيجاد إيميلات الشركات للتسويق: المسموح والممنوع', 'en' => 'Finding company emails for marketing: what is allowed and what is not'],
    ['key' => 'online-store-woocommerce-or-salla', 'product' => 'online-store-development',
        'ar' => 'تبدأ متجر إلكتروني في مصر أو الخليج: ووكومرس ولا سلة ولا كود مخصص', 'en' => 'Starting an online store in Egypt or the Gulf: WooCommerce, Salla or custom code'],
    ['key' => 'whatsapp-chatbot-when-worth-it', 'product' => 'ai-chatbot-whatsapp-automation',
        'ar' => 'شات بوت واتساب لخدمة العملاء: إمتى يستاهل وإمتى لأ', 'en' => 'A WhatsApp chatbot for customer service: when it is worth it and when it is not'],
    ['key' => 'landing-page-basics', 'product' => 'landing-page-design',
        'ar' => 'صفحة هبوط بتبيع: العناصر الأساسية اللي مفيش عنها بديل', 'en' => 'A landing page that sells: the essentials with no substitute'],
    ['key' => 'small-business-backup-plan', 'product' => 'shop-manager-pro',
        'ar' => 'خطة نسخ احتياطي بسيطة لبيانات محلك أو شركتك الصغيرة', 'en' => 'A simple backup plan for the data of your shop or small company'],
];
