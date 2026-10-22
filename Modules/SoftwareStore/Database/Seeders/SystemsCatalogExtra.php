<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Pharmacy, restaurant and dental systems. Same shape as the systems in SystemsCatalog.
 * The facts come from the systems' READMEs, seeders and test suites. Limits are stated on purpose:
 * the ETA e-receipt connector is built to the documentation but not tested against the real ETA, no controlled-drug register,
 * demo data is fake, no online payment.
 */
class SystemsCatalogExtra
{
    private const ETA_AR = 'أيوه، فيه ربط جاهز بالإيصال الإلكتروني لمصلحة الضرائب (ETA). كتبناه على التوثيق الرسمي للمصلحة واختبرناه على خادم تجريبي بس، ومجربناهوش على خادم المصلحة الحقيقي ومعندناش اعتماد منها. عشان تشغله لازم تسجّل جهاز الكاشير في بوابة المصلحة وتحصل على بياناته وأكواد الأصناف، وتجرّبه على بيئة التجربة الأول. اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني.';

    private const ETA_EN = 'Yes, a connector for the Egyptian Tax Authority e-receipt (ETA) is included. It is written to ETA\'s public documentation and tested only against a simulated server: it has not been tested against the real ETA and it is not certified by ETA. To use it you register your cash register in the ETA portal, get its credentials and your item codes, and try it on the ETA test environment first. Ask your accountant whether your business must issue e-receipts.';

    /** @return array<int, array<string, mixed>> */
    public static function systems(): array
    {
        return [self::pharmacy(), self::restaurant(), self::dental()];
    }

    private static function pharmacy(): array
    {
        return [
            'slug' => 'pharmacy-system', 'colors' => ['accent' => '#0d9488', 'ink' => '#06231f'],
            'name_en' => 'Pharmacy Management System', 'name_ar' => 'نظام إدارة الصيدلية',
            'tagline_en' => 'Point of sale, batches and expiry alerts for a pharmacy, with first-expiry-first-out selling.',
            'tagline_ar' => 'كاشير ودفعات وتنبيهات صلاحية لصيدلية، مع البيع بالأقرب انتهاءً أولاً.',
            'ar' => [
                'kicker' => 'الصيدليات', 'headline' => 'صيدليتك من غير أدوية منتهية ولا مخزون مجهول.',
                'subheadline' => 'كل شراء بيعمل دفعة بتاريخ صلاحيتها، والكاشير بيبيع من أقرب دفعة تنتهي. وتنبيهات الصلاحية والنواقص قدامك كل يوم.',
                'who' => 'أصحاب الصيدليات وسلاسل الصيدليات الصغيرة (أكتر من فرع) اللي عايزين كاشير ومخزون بتواريخ الصلاحية.',
                'problems_title' => 'ليه الصيدلية بتخسر؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'أدوية بتنتهي على الرف', 'body' => 'مفيش تنبيه بالأصناف القريبة من الانتهاء، والخسارة بتظهر متأخر.'],
                    ['icon' => 'box', 'title' => 'علبة وشريط', 'body' => 'البيع بالشريط بيلخبط الجرد لو المخزون مش متسجل بالوحدة الأصغر.'],
                    ['icon' => 'file', 'title' => 'ديون وموردين', 'body' => 'ديون العملاء وحسابات الموردين على ورق أو في الدماغ.'],
                ],
                'showcase_title' => 'الكاشير والمخزون والصلاحية', 'showcase' => [
                    ['title' => 'كاشير بالباركود', 'body' => 'بحث بالباركود أو الاسم التجاري أو العلمي، وخصم، وبيع آجل، وطباعة 80 مم أو 58 مم أو A4.'],
                    ['title' => 'تنبيهات الصلاحية', 'body' => 'منتهي، وخلال 30 و60 و90 يوم، بقيمة التكلفة. والمنتهي مبيعش أبدًا.'],
                    ['title' => 'دفعات وتقارير', 'body' => 'رقم الدفعة وتاريخ الانتهاء على الفاتورة، وتقارير المبيعات والربح والنواقص وديون العملاء.'],
                ],
                'pillars_title' => 'أربع حاجات تحمي صيدليتك',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'الأقرب انتهاءً أولاً', 'body' => 'البيع بيتقسم على الدفعات تلقائيًا لو الدفعة الأولى خلصت.'],
                    ['icon' => 'shield', 'title' => 'روشتة مطلوبة', 'body' => 'الدواء اللي محتاج روشتة لازم الكاشير يأكد إنه شافها، وممكن يسجل اسم الدكتور والمريض.'],
                    ['icon' => 'refresh', 'title' => 'مرتجعات وتحويلات', 'body' => 'مرتجع عميل ومرتجع مورد وتحويل بين الفروع وتسوية مخزون.'],
                    ['icon' => 'users', 'title' => 'صلاحيات', 'body' => 'كاشير وصيدلي وأمين مخزن ومدير، وكل واحد يشوف شغله.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الصيدلية',
                    'before' => [['b' => 'الصلاحية', 't' => 'جرد يدوي بيكتشف المنتهي متأخر.'], ['b' => 'البيع', 't' => 'بيع من أي علبة والنتيجة منتهي على الرف.'], ['b' => 'الموردين', 't' => 'حسابات على ورق.']],
                    'after' => [['b' => 'الصلاحية', 't' => 'تنبيه قبل الانتهاء بـ 30 و60 و90 يوم.'], ['b' => 'البيع', 't' => 'الأقرب انتهاءً أولاً تلقائيًا.'], ['b' => 'الموردين', 't' => 'رصيد كل مورد ومرتجعاته.']]],
                'metrics' => [['value' => '60', 'label' => 'دواء تجريبي في النسخة التجريبية'], ['value' => '3', 'label' => 'أحجام طباعة للإيصال'], ['value' => '152', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيحفظ المخزون بالشريط وتقدر تبيع بالعلبة أو بالشريط. كل بند شراء بيعمل دفعة، والكاشير بياخد من الدفعة الأقرب انتهاءً، والمنتهي مبيعش حتى لو اتمسح الباركود. المرتجع بيرجع لنفس الدفعة إلا لو انتهت.', 'جوه النظام: الأدوية والمخزون، والمشتريات والموردين، والعملاء والديون، وتحويلات الفروع، وتسويات المخزون وإعدام المنتهي، وملصقات الأسعار بباركود، وتقارير يومية. فيه بيانات تجريبية خيالية عشان تجرب قبل ما تبدأ.'],
                'list' => ['كاشير بالباركود والاسم التجاري والعلمي', 'دفعات وتواريخ صلاحية وبيع الأقرب انتهاءً أولاً', 'تنبيهات المنتهي والقريب من الانتهاء والنواقص', 'بيع بالعلبة أو بالشريط', 'تأكيد الروشتة للأدوية اللي بتحتاجها', 'مشتريات وموردين ومرتجعات', 'تحويلات بين الفروع وتسويات مخزون', 'ملصقات أسعار بباركود وإيصالات 80 و58 مم وA4', 'تقارير المبيعات والربح والديون', 'إرسال المبيعات والمرتجعات للإيصال الإلكتروني (ETA) لكل فرع، ويحتاج بيانات مسجّلة عندك'],
                'faq' => [
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => self::ETA_AR],
                    ['q' => 'فيه سجل للمخدرات والأدوية المراقبة؟', 'a' => 'لا. النظام بيسجل بيانات الروشتة على الفاتورة بس، ومفيهوش سجل رسمي للأدوية المراقبة. استخدم السجل الورقي المطلوب قانونًا.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الأدوية والعملاء في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم صيدليتك؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'طرق الدفع المسجلة: نقدي وبطاقة وتحويل ومحفظة (تسجيل فقط، من غير دفع أونلاين)'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. النظام بيسجل طريقة الدفع (نقدي أو بطاقة أو تحويل أو محفظة) لكنه مبيحصلش الدفع بنفسه.'],
            ],
            'en' => [
                'kicker' => 'Pharmacies', 'headline' => 'A pharmacy with no expired medicine on the shelf and no mystery stock.',
                'subheadline' => 'Every purchase creates a batch with its expiry date, and the cashier sells from the batch that expires first. Expiry and low-stock alerts are in front of you every day.',
                'who' => 'Pharmacy owners and small chains (more than one branch) who want a cashier and stock with expiry dates.',
                'problems_title' => 'Why does a pharmacy lose money?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'Medicine expires on the shelf', 'body' => 'No warning for items close to expiry, so the loss shows up late.'],
                    ['icon' => 'box', 'title' => 'Box and strip', 'body' => 'Selling by the strip breaks stock counts when stock is not kept in the smaller unit.'],
                    ['icon' => 'file', 'title' => 'Debts and suppliers', 'body' => 'Customer debts and supplier accounts live on paper or in someone\'s head.'],
                ],
                'showcase_title' => 'Cashier, stock and expiry', 'showcase' => [
                    ['title' => 'Barcode cashier', 'body' => 'Search by barcode, trade name or generic name, discounts, credit sales, and print on 80 mm, 58 mm or A4.'],
                    ['title' => 'Expiry alerts', 'body' => 'Expired, within 30, 60 and 90 days, valued at cost. Expired stock can never be sold.'],
                    ['title' => 'Batches and reports', 'body' => 'Batch number and expiry on the receipt, plus sales, profit, low-stock and customer debt reports.'],
                ],
                'pillars_title' => 'Four things that protect your pharmacy',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'First expiry, first out', 'body' => 'A sale is split across batches automatically when the first batch runs out.'],
                    ['icon' => 'shield', 'title' => 'Prescription required', 'body' => 'For prescription medicines the cashier must confirm the prescription was seen, and can note the doctor and patient.'],
                    ['icon' => 'refresh', 'title' => 'Returns and transfers', 'body' => 'Customer returns, supplier returns, branch transfers and stock adjustments.'],
                    ['icon' => 'users', 'title' => 'Roles', 'body' => 'Cashier, pharmacist, stock keeper and manager, each sees their own work.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the pharmacy system',
                    'before' => [['b' => 'Expiry', 't' => 'Manual counts find expired stock late.'], ['b' => 'Selling', 't' => 'Any box is sold and the old one expires.'], ['b' => 'Suppliers', 't' => 'Accounts on paper.']],
                    'after' => [['b' => 'Expiry', 't' => 'Alerts at 30, 60 and 90 days.'], ['b' => 'Selling', 't' => 'First expiry first out, automatically.'], ['b' => 'Suppliers', 't' => 'Each supplier balance and returns.']]],
                'metrics' => [['value' => '60', 'label' => 'Sample medicines in the demo'], ['value' => '3', 'label' => 'Receipt print sizes'], ['value' => '152', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['Stock is kept in strips and you can sell by the box or by the strip. Every purchase line creates a batch, the cashier takes from the batch that expires first, and expired stock is never sold. A return goes back to the same batch unless it has expired.', 'Inside: medicines and stock, purchases and suppliers, customers and debts, branch transfers, stock adjustments and write-off of expired stock, price labels with barcodes, and daily reports. It ships with fictional sample data so you can try it first.'],
                'list' => ['Cashier with barcode, trade name and generic name search', 'Batches, expiry dates and first-expiry-first-out selling', 'Alerts for expired, near-expiry and low stock', 'Sell by the box or by the strip', 'Prescription confirmation for medicines that need one', 'Purchases, suppliers and returns', 'Branch transfers and stock adjustments', 'Barcode price labels and 80 mm, 58 mm and A4 receipts', 'Sales, profit and debt reports', 'Sales and returns sent to the tax e-receipt (ETA), one cash register per branch (needs your own registration)'],
                'faq' => [
                    ['q' => 'Does it support the tax e-receipt?', 'a' => self::ETA_EN],
                    ['q' => 'Is there a controlled-drug register?', 'a' => 'No. The system only notes prescription details on the sale and has no official register for controlled drugs. Keep the paper register the law requires.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All medicines and customers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to organize your pharmacy?',
                'included' => ['Arabic and English with right-to-left support', 'Recorded payment methods: cash, card, transfer and wallet (recorded only, no online payment)'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. The system records the payment method (cash, card, transfer or wallet) but does not collect the payment itself.'],
            ],
        ];
    }

    private static function restaurant(): array
    {
        return [
            'slug' => 'restaurant-pos-system', 'colors' => ['accent' => '#b91c1c', 'ink' => '#240a0a'],
            'name_en' => 'Restaurant POS System', 'name_ar' => 'نظام كاشير وإدارة المطعم',
            'tagline_en' => 'Table tabs, a kitchen display, split bills and an end-of-day report for a restaurant.',
            'tagline_ar' => 'طاولات وشاشة مطبخ وتقسيم فاتورة وتقرير نهاية اليوم لمطعم.',
            'ar' => [
                'kicker' => 'المطاعم', 'headline' => 'من الطاولة للمطبخ للحساب، من غير ورق ولا صراخ.',
                'subheadline' => 'الجرسون يفتح الطاولة ويبعت الطلب على المطبخ على دفعات، والمطبخ يشوف التذاكر بالألوان، والكاشير يقسم الحساب ويقفل اليوم.',
                'who' => 'أصحاب المطاعم والكافيهات اللي عندهم طاولات وجرسونات ومطبخ، وعايزين كاشير وشاشة مطبخ.',
                'problems_title' => 'ليه الخدمة بتتلخبط؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'ورقة الطلب', 'body' => 'الطلب بيتكتب بإيد ويوصل المطبخ ناقص أو متأخر.'],
                    ['icon' => 'clock', 'title' => 'مفيش وقت للطلب', 'body' => 'محدش عارف الطلب مستني بقاله قد إيه.'],
                    ['icon' => 'calculator', 'title' => 'تقسيم الحساب', 'body' => 'تقسيم الفاتورة بين الأصحاب بيعمل خناقة وفروق في الجنيه.'],
                ],
                'showcase_title' => 'الطاولات والمطبخ والحساب', 'showcase' => [
                    ['title' => 'خريطة الطاولات', 'body' => 'فاضية ومشغولة وطالبة الحساب، بالوقت المنقضي وعلامة إن الأكل جاهز.'],
                    ['title' => 'شاشة المطبخ', 'body' => 'تذاكر لكل محطة (مشويات، بار، حلويات)، وألوان تتحول للأصفر ثم الأحمر مع الوقت، وجرس عند وصول طلب جديد.'],
                    ['title' => 'تقسيم وإغلاق', 'body' => 'قسّم الحساب بالتساوي أو بالأصناف، وكل جزء يتدفع ويتطبع لوحده، وتقرير Z في آخر اليوم.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم المطعم',
                'pillars' => [
                    ['icon' => 'zap', 'title' => 'طلبات على دفعات', 'body' => 'الجرسون يضيف أصناف ويبعتها، ويضيف دفعة تانية لنفس الطاولة.'],
                    ['icon' => 'check', 'title' => 'تعديلات وملاحظات', 'body' => 'درجة الاستواء والإضافات بأسعارها وملاحظة حرة، وكلها بتظهر على تذكرة المطبخ.'],
                    ['icon' => 'shield', 'title' => 'صلاحيات وسجل', 'body' => 'الجرسون يشوف طاولاته بس. الإلغاء والخصم بيحتاجوا سبب وبيتسجلوا باسم المستخدم.'],
                    ['icon' => 'chart', 'title' => 'تقرير نهاية اليوم', 'body' => 'مبيعات حسب النوع والقسم والجرسون، مع الخصومات والإلغاءات والضريبة ورسوم الخدمة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام المطعم',
                    'before' => [['b' => 'الطلب', 't' => 'ورقة بتتوه أو بتتفهم غلط.'], ['b' => 'المطبخ', 't' => 'مفيش تتبع للوقت.'], ['b' => 'الحساب', 't' => 'تقسيم يدوي وفروق.']],
                    'after' => [['b' => 'الطلب', 't' => 'يوصل المطبخ بالتفاصيل والملاحظات.'], ['b' => 'المطبخ', 't' => 'تذاكر بألوان ووقت.'], ['b' => 'الحساب', 't' => 'تقسيم بالقرش وكل جزء يتطبع.']]],
                'metrics' => [['value' => '12', 'label' => 'طاولة في النسخة التجريبية'], ['value' => '4', 'label' => 'مراحل لتذكرة المطبخ'], ['value' => '132', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير الطاولات والمطبخ والكاشير. الخدمة (12%) والضريبة (14%) نسب افتراضية يغيرها المالك، وبتتطبق على الأكل في المطعم بس. وبتتسجل على كل طاولة وقت فتحها.', 'جوه النظام: خريطة الطاولات، وشاشة مطبخ بمحطات، والتعديلات والملاحظات، وتقسيم الحساب، والتيك أواي والتوصيل (وتذاكرهم بتظهر في المطبخ)، وقائمة الطعام والإعدادات، وتقرير Z. فيه بيانات تجريبية خيالية.'],
                'list' => ['خريطة الطاولات وفتح الطاولة وطلبات على دفعات', 'شاشة مطبخ بمحطات وألوان ووقت وجرس', 'تعديلات إجبارية واختيارية وملاحظة لكل صنف', 'تقسيم الحساب بالتساوي أو بالأصناف', 'خدمة وضريبة بنسب يحددها المالك', 'صلاحيات للجرسون والكاشير والمالك وسجل للإلغاء والخصم', 'تيك أواي وتوصيل', 'تقرير نهاية اليوم (Z) وطباعة 80 مم', 'إرسال الفواتير المدفوعة وأجزاء الفاتورة المقسّمة للإيصال الإلكتروني (ETA)، ويحتاج بيانات مسجّلة عندك'],
                'faq' => [
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => self::ETA_AR],
                    ['q' => 'فيه مخزون وحجز طاولات؟', 'a' => 'لا. النسخة الحالية مفيهاش تحكم في المخزون ولا حجز طاولات ولا إشعارات.'],
                    ['q' => 'البيانات التجريبية والصور حقيقية؟', 'a' => 'لا. الأصناف والأسعار تجريبية، وصور الأكل في النسخة التجريبية قليلة. هتضيف صور مطعمك من قائمة الطعام.'],
                ],
                'cta_title' => 'جاهز تنظم مطعمك؟',
            ],
            'en' => [
                'kicker' => 'Restaurants', 'headline' => 'From table to kitchen to bill, with no paper and no shouting.',
                'subheadline' => 'The waiter opens a table and sends orders to the kitchen in rounds, the kitchen sees colour-coded tickets, and the cashier splits the bill and closes the day.',
                'who' => 'Restaurant and cafe owners with tables, waiters and a kitchen who want a cashier and a kitchen display.',
                'problems_title' => 'Why does service get messy?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Paper orders', 'body' => 'Orders are written by hand and reach the kitchen late or incomplete.'],
                    ['icon' => 'clock', 'title' => 'No order timer', 'body' => 'Nobody knows how long an order has been waiting.'],
                    ['icon' => 'calculator', 'title' => 'Splitting the bill', 'body' => 'Splitting a bill between friends causes arguments and rounding gaps.'],
                ],
                'showcase_title' => 'Tables, kitchen and bill', 'showcase' => [
                    ['title' => 'Table map', 'body' => 'Free, occupied and bill requested, with elapsed time and a food ready flag.'],
                    ['title' => 'Kitchen display', 'body' => 'Tickets per station (grill, bar, desserts), colours turn amber then red with time, and a chime for new tickets.'],
                    ['title' => 'Split and close', 'body' => 'Split the bill equally or by items, each part is paid and printed on its own, and a Z report ends the day.'],
                ],
                'pillars_title' => 'Four things that organize the restaurant',
                'pillars' => [
                    ['icon' => 'zap', 'title' => 'Orders in rounds', 'body' => 'The waiter adds items and sends them, then adds another round to the same table.'],
                    ['icon' => 'check', 'title' => 'Modifiers and notes', 'body' => 'Cooking level and extras with prices, plus a free note, all shown on the kitchen ticket.'],
                    ['icon' => 'shield', 'title' => 'Roles and audit log', 'body' => 'Waiters see only their own tables. Voids and discounts need a reason and are logged with the user.'],
                    ['icon' => 'chart', 'title' => 'End-of-day report', 'body' => 'Sales by order type, category and waiter, with discounts, voids, tax and service charge.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the restaurant system',
                    'before' => [['b' => 'Ordering', 't' => 'A paper slip that gets lost or misread.'], ['b' => 'Kitchen', 't' => 'No time tracking.'], ['b' => 'Bill', 't' => 'Manual split and rounding gaps.']],
                    'after' => [['b' => 'Ordering', 't' => 'Reaches the kitchen with details and notes.'], ['b' => 'Kitchen', 't' => 'Colour tickets with timers.'], ['b' => 'Bill', 't' => 'Split to the cent, each part printed.']]],
                'metrics' => [['value' => '12', 'label' => 'Tables in the demo'], ['value' => '4', 'label' => 'Stages of a kitchen ticket'], ['value' => '132', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs tables, the kitchen and the cashier. Service charge (12%) and tax (14%) are default rates the owner can change, and they apply to dine-in only. They are copied onto each tab when it opens.', 'Inside: a table map, a kitchen display with stations, modifiers and notes, split bills, take-away and delivery (their tickets also show in the kitchen), the menu and settings, and a Z report. It ships with fictional sample data.'],
                'list' => ['Table map, opening a table and orders in rounds', 'Kitchen display with stations, colours, timers and a chime', 'Required and optional modifiers and a note on every item', 'Equal or by-item split bills', 'Service charge and tax set by the owner', 'Roles for waiter, cashier and owner, with a log for voids and discounts', 'Take-away and delivery', 'End-of-day (Z) report and 80 mm printing', 'Paid bills and split shares sent to the tax e-receipt (ETA) (needs your own registration)'],
                'faq' => [
                    ['q' => 'Does it support the tax e-receipt?', 'a' => self::ETA_EN],
                    ['q' => 'Does it have stock control and table reservations?', 'a' => 'No. This version has no stock control, table reservations or notifications.'],
                    ['q' => 'Are the demo data and pictures real?', 'a' => 'No. The dishes and prices are sample data and the demo has few food pictures. You add your own pictures from the menu screen.'],
                ],
                'cta_title' => 'Ready to organize your restaurant?',
            ],
        ];
    }

    private static function dental(): array
    {
        return [
            'slug' => 'dental-clinic-system', 'colors' => ['accent' => '#0e7490', 'ink' => '#062029'],
            'name_en' => 'Dental Clinic System', 'name_ar' => 'نظام عيادة الأسنان',
            'tagline_en' => 'A tooth chart, treatment plans, instalments and X-ray images for a dental clinic.',
            'tagline_ar' => 'مخطط أسنان وخطط علاج وأقساط وصور أشعة لعيادة أسنان.',
            'ar' => [
                'kicker' => 'عيادات الأسنان', 'headline' => 'مخطط الأسنان وخطة العلاج والأقساط في مكان واحد.',
                'subheadline' => 'الدكتور يسجل حالة كل سن على مخطط تفاعلي، ويعمل خطة علاج بأسعار قائمة العيادة، والمريض يوافق عليها، والاستقبال يتابع الدفعات والأقساط.',
                'who' => 'عيادات الأسنان اللي عايزة حجز أونلاين ومخطط أسنان وخطط علاج بأقساط.',
                'problems_title' => 'ليه ملف مريض الأسنان بيضيع؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'حالة الأسنان في ورقة', 'body' => 'تاريخ كل سن مفيش مكان واضح يتسجل فيه.'],
                    ['icon' => 'calculator', 'title' => 'علاج بأقساط', 'body' => 'مين دفع إيه وفاضل كام، من غير جدول واضح.'],
                    ['icon' => 'lock', 'title' => 'صور الأشعة', 'body' => 'صور المرضى على موبايلات أو فلاشات من غير حماية.'],
                ],
                'showcase_title' => 'من المخطط للحساب', 'showcase' => [
                    ['title' => 'مخطط الأسنان', 'body' => 'ترقيم FDI للأسنان الدائمة واللبنية، وتسع حالات وأسطح لكل سن وملاحظة وتاريخ بالطبيب والتاريخ.'],
                    ['title' => 'خطة العلاج', 'body' => 'إجراءات من قائمة الأسعار على كل سن، والسعر بيتاخد من القائمة مش من النموذج، والمريض يوافق برابط خاص.'],
                    ['title' => 'الدفعات والأقساط', 'body' => 'دفعات جزئية بإيصالات مرقمة، وجدول أقساط، وقائمة المتأخرين برابط واتساب من غير أي بيانات طبية.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم العيادة',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'ربط الزيارة بالخطة', 'body' => 'علّم الإجراء تم في الزيارة وتتحدث الخطة وحالة السن.'],
                    ['icon' => 'clock', 'title' => 'حجز أونلاين', 'body' => 'المريض يختار الدكتور ويحجز، والاستقبال يشوف قائمة اليوم والطابور.'],
                    ['icon' => 'lock', 'title' => 'صور خاصة', 'body' => 'صور بس (JPG وPNG وWebP حتى 5 ميجا)، بتتخزن بأسماء عشوائية، والاستقبال مبيفتحهاش، وكل فتح بيتسجل.'],
                    ['icon' => 'users', 'title' => 'صلاحيات وسجل', 'body' => 'المالك والدكتور والاستقبال، وسجل مراجعة لكل دخول على بيانات حساسة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام العيادة',
                    'before' => [['b' => 'الحالة', 't' => 'ملاحظات على ورق.'], ['b' => 'الأقساط', 't' => 'متابعة بالدماغ.'], ['b' => 'الصور', 't' => 'على موبايلات وفلاشات.']],
                    'after' => [['b' => 'الحالة', 't' => 'مخطط وتاريخ لكل سن.'], ['b' => 'الأقساط', 't' => 'جدول ومتأخرات برابط واتساب.'], ['b' => 'الصور', 't' => 'مخزنة بخصوصية ومسجّل كل فتح.']]],
                'metrics' => [['value' => '9', 'label' => 'حالات لكل سن'], ['value' => '52', 'label' => 'سن (دائمة ولبنية) على المخطط'], ['value' => '106', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام مبني على نظام حجز العيادات ومضاف له الجانب الخاص بالأسنان. المخطط ترقيمه FDI ومعاه حالة وأسطح (M D O B L I) وملاحظة وتاريخ لكل سن. خطط العلاج بتتقفل بعد موافقة المريض لحد ما تتفتح تاني، وبتتطبع بالعربي والإنجليزي على A4.', 'جوه النظام أيضًا: الحجز والطابور والروشتات والتقييمات وسجل المراجعة، وحساب كل مريض، وإيصالات 80 مم وA4. فيه بيانات تجريبية خيالية، وصور الأشعة فيها كلها مكتوب عليها "تجريبية".'],
                'list' => ['مخطط أسنان تفاعلي بترقيم FDI (دائمة ولبنية)', 'خطط علاج بأسعار القائمة وموافقة المريض وطباعة', 'ربط الزيارة بإجراءات الخطة', 'دفعات جزئية وإيصالات وجدول أقساط وقائمة متأخرين', 'صور أشعة خاصة بسجل فتح', 'حجز أونلاين وقائمة اليوم والطابور', 'روشتات وتقييمات وسجل مراجعة', 'صلاحيات للمالك والدكتور والاستقبال', 'إرسال الدفعات النقدية للإيصال الإلكتروني (ETA)، ويحتاج بيانات مسجّلة عندك'],
                'faq' => [
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => self::ETA_AR],
                    ['q' => 'بيدعم صور DICOM أو فحص اللثة أو التأمين؟', 'a' => 'لا. مفيهوش DICOM ولا مخطط لثة ولا تأمين ولا رسائل SMS. الصور المدعومة JPG وPNG وWebP بس.'],
                    ['q' => 'بيانات المرضى محمية قانونيًا بالنظام ده؟', 'a' => 'النظام بيحمي الصور وبيسجل الدخول، لكنه مش بيضمن التزامك بقانون حماية البيانات. المسؤولية القانونية على العيادة، واسأل مستشارك القانوني.'],
                ],
                'cta_title' => 'جاهز تنظم عيادتك؟',
            ],
            'en' => [
                'kicker' => 'Dental clinics', 'headline' => 'The tooth chart, the treatment plan and the instalments in one place.',
                'subheadline' => 'The dentist records the state of each tooth on an interactive chart and builds a treatment plan with the clinic\'s own prices. The patient accepts it, and reception follows payments and instalments.',
                'who' => 'Dental clinics that want online booking, a tooth chart and treatment plans paid in instalments.',
                'problems_title' => 'Why does a dental file get lost?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Tooth state on paper', 'body' => 'There is no clear place for the history of each tooth.'],
                    ['icon' => 'calculator', 'title' => 'Treatment on instalments', 'body' => 'Who paid what and how much is left, with no clear schedule.'],
                    ['icon' => 'lock', 'title' => 'X-ray images', 'body' => 'Patient images sit on phones or flash drives with no protection.'],
                ],
                'showcase_title' => 'From the chart to the bill', 'showcase' => [
                    ['title' => 'Tooth chart', 'body' => 'FDI numbering for permanent and primary teeth, nine statuses, surfaces, a note and a history with doctor and date for each tooth.'],
                    ['title' => 'Treatment plan', 'body' => 'Procedures from the price list on each tooth, the price comes from the list and never from the form, and the patient accepts through a private link.'],
                    ['title' => 'Payments and instalments', 'body' => 'Partial payments with numbered receipts, an instalment schedule, and an overdue list with WhatsApp links that carry no medical text.'],
                ],
                'pillars_title' => 'Four things that organize the clinic',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Visit linked to the plan', 'body' => 'Tick a procedure done during the visit and the plan and the tooth update.'],
                    ['icon' => 'clock', 'title' => 'Online booking', 'body' => 'The patient picks a doctor and books, and reception sees the day list and the queue.'],
                    ['icon' => 'lock', 'title' => 'Private images', 'body' => 'Images only (JPG, PNG, WebP up to 5 MB), stored under random names. Reception cannot open them and every open is logged.'],
                    ['icon' => 'users', 'title' => 'Roles and audit log', 'body' => 'Owner, doctor and reception, with an audit log for access to sensitive data.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the clinic system',
                    'before' => [['b' => 'Records', 't' => 'Notes on paper.'], ['b' => 'Instalments', 't' => 'Followed from memory.'], ['b' => 'Images', 't' => 'On phones and flash drives.']],
                    'after' => [['b' => 'Records', 't' => 'A chart and a history for each tooth.'], ['b' => 'Instalments', 't' => 'A schedule and overdue list with WhatsApp links.'], ['b' => 'Images', 't' => 'Stored privately, every open logged.']]],
                'metrics' => [['value' => '9', 'label' => 'Statuses for each tooth'], ['value' => '52', 'label' => 'Teeth (permanent and primary) on the chart'], ['value' => '106', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system is built on the clinic booking system with the dental side added. The chart uses FDI numbering with a status, surfaces (M D O B L I), a note and a history for each tooth. A treatment plan is locked after the patient accepts until it is reopened, and prints in Arabic and English on A4.', 'Inside as well: booking, the queue, prescriptions, reviews and an audit log, each patient\'s account, and 80 mm and A4 receipts. It ships with fictional sample data, and every X-ray in the demo is marked as a demo image.'],
                'list' => ['Interactive tooth chart with FDI numbering (permanent and primary)', 'Treatment plans with list prices, patient acceptance and printing', 'Visits linked to plan procedures', 'Partial payments, receipts, an instalment schedule and an overdue list', 'Private X-ray images with an access log', 'Online booking, the day list and the queue', 'Prescriptions, reviews and an audit log', 'Roles for owner, doctor and reception', 'Cash payments sent to the tax e-receipt (ETA) (needs your own registration)'],
                'faq' => [
                    ['q' => 'Does it support the tax e-receipt?', 'a' => self::ETA_EN],
                    ['q' => 'Does it support DICOM, gum charting or insurance?', 'a' => 'No. It has no DICOM, periodontal charting, insurance or SMS. The only supported images are JPG, PNG and WebP.'],
                    ['q' => 'Does this system make patient data legally compliant?', 'a' => 'The system protects images and logs access, but it does not guarantee that you comply with data protection law. The legal duty stays with the clinic, so ask your legal adviser.'],
                ],
                'cta_title' => 'Ready to organize your clinic?',
            ],
        ];
    }
}
