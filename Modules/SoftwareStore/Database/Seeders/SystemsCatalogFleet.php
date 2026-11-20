<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Fleet and transport system. Same shape as the systems in SystemsCatalog (listed in SystemsCatalogSaas).
 * The facts come from the system's README, seeders and test suite. Limits are stated on purpose: no ETA e-invoice (that
 * belongs to an accounting system), no general ledger or payroll, GPS vendors push positions (no route planning or arrival
 * estimate), demo data is fake.
 */
class SystemsCatalogFleet
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'fleet-system', 'colors' => ['accent' => '#0f766e', 'ink' => '#042f2c'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'Fleet and Transport Management System', 'name_ar' => 'نظام إدارة أساطيل النقل',
            'tagline_en' => 'Trips from loading to proof of delivery, waybills, fuel, driver cash, maintenance, invoices and profit per trip and vehicle for a transport company.',
            'tagline_ar' => 'رحلات من التحميل لإثبات التسليم وبوالص شحن ووقود وعهد سواقين وصيانة وفواتير ومكسب كل رحلة وعربية لشركة نقل.',
            'ar' => [
                'kicker' => 'شركات النقل والتوزيع', 'headline' => 'كل رحلة كسبت كام، وكل عربية صرفت كام.',
                'subheadline' => 'الرحلة بتمشي من مخطط لمحمّلة لفي الطريق لتم التسليم، والسواق بيحدثها من الموبايل ويصور البوليصة الموقعة، والوقود والكارتة والعهدة بيتحسبوا على الرحلة، والفاتورة بتطلع من الرحلات اللي اتسلمت.',
                'who' => 'شركات النقل الثقيل واللوجستيات وأساطيل التوزيع وأتوبيسات المدارس والميكروباصات وتأجير العربيات الصغير.',
                'problems_title' => 'ليه شركة النقل مش عارفة بتكسب منين؟',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'مكسب الرحلة مش معروف', 'body' => 'الوقود والكارتة واليومية والتحميل بيتصرفوا من غير ما يتربطوا بالرحلة والعميل.'],
                    ['icon' => 'file', 'title' => 'رخص وتأمين بتخلص', 'body' => 'رخصة تسيير أو تأمين أو رخصة سواق بتنتهي ومحدش واخد باله لحد ما العربية تتوقف.'],
                    ['icon' => 'zap', 'title' => 'وقود بيضيع', 'body' => 'لترات أكتر من المعتاد أو عداد مبيتحركش، ومحدش بيقارن الاستهلاك بالمستهدف.'],
                ],
                'showcase_title' => 'الرحلة والخريطة وشاشة السواق', 'showcase' => [
                    ['title' => 'صفحة الرحلة', 'body' => 'العميل والتحميل والتسليم والحمولة والعربية والسواق والسعر، والإيراد والوقود والمصروفات والربح، وبوليصة شحن A4.'],
                    ['title' => 'الخريطة', 'body' => 'آخر موقع لكل عربية من جهاز التتبع على خريطة، والعربيات اللي موقعها اتأخر.'],
                    ['title' => 'شاشة السواق', 'body' => 'رحلاتي، وزرار للخطوة الجاية، والإبلاغ عن تأخير، وإثبات التسليم بالصورة والتوقيع، والمصروفات والوقود والعهدة.'],
                ],
                'pillars_title' => 'أربع حاجات بتحمي الأسطول',
                'pillars' => [
                    ['icon' => 'shield', 'title' => 'ممنوع التحميل', 'body' => 'العربية اللي رخصتها أو تأمينها منتهي أو في الورشة، والسواق اللي رخصته منتهية، مينفعش يتحمّلوا.'],
                    ['icon' => 'zap', 'title' => 'استهلاك الوقود', 'body' => 'لترات لكل 100 كم بين كل تفويلتين قدام مستهدف العربية، وأي تفويلة زيادة عن المسموح بتتعلّم.'],
                    ['icon' => 'wrench', 'title' => 'صيانة وقطع غيار وكاوتش', 'body' => 'خطط صيانة بالكيلو أو بالأيام، وأوامر شغل بقطع من المخزن، وكاوتش برقمه المسلسل وكيلومتراته.'],
                    ['icon' => 'calculator', 'title' => 'فواتير ومكسب', 'body' => 'فاتورة من الرحلات المسلمة بالقيمة المضافة والخصم من المنبع، وكشف حساب، ومكسب كل رحلة وعربية وعميل وخط.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الأسطول',
                    'before' => [['b' => 'الرحلات', 't' => 'مكالمات للسواق عشان تعرف هو فين.'], ['b' => 'الوقود', 't' => 'فواتير بنزينة في درج.'], ['b' => 'الأوراق', 't' => 'رخص بتخلص فجأة.']],
                    'after' => [['b' => 'الرحلات', 't' => 'السواق بيحدث الرحلة من الموبايل.'], ['b' => 'الوقود', 't' => 'استهلاك كل عربية قدام المستهدف.'], ['b' => 'الأوراق', 't' => 'قائمة بالمنتهي والقريب ومنع التحميل.']]],
                'metrics' => [['value' => '5', 'label' => 'خطوات للرحلة من مخطط لاستلام الإثبات'], ['value' => '6', 'label' => 'أدوار جاهزة: المالك ومدير الأسطول والمشغل والمحاسب ومشرف الصيانة وأمين المخزن'], ['value' => '126', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير أسطول شركة نقل: العربيات بنمرتها المصرية ونوعها وحمولتها وملكيتها (ملك أو إيجار أو مقاول نقل)، وأوراقها، والسواقين بدرجة رخصتهم اللي بتحدد يسوقوا إيه، والعملاء بأسعار لكل خط.', 'جوه النظام: الرحلات بخطواتها وبوليصة الشحن وإثبات التسليم، ومصروفات الرحلة، وعهد السواقين وتسويتها، والوقود وكروت البنزين، والفواتير والتحصيل وأعمار الديون، والصيانة ومخزن قطع الغيار والكاوتش، والمكسب، وشاشات للسواق على الموبايل، وبوابة للعميل يتابع شحناته، ونقطة ربط لأجهزة التتبع. نسخة واحدة بتخدم أكتر من شركة، وكل شركة بتشوف بياناتها بس. فيه بيانات تجريبية خيالية لسنة.'],
                'list' => ['عربيات بالنمرة المصرية وأوراقها وتنبيه بالمنتهي والقريب', 'سواقين بدرجة الرخصة ومخالفات وتقييم أداء', 'عملاء بأسعار لكل خط بالرحلة أو الطن أو الكيلو أو اليوم', 'رحلات بخطوات وبوليصة شحن A4 وإثبات تسليم بالصورة والتوقيع', 'مصروفات رحلات وعهد سواقين وتسويتها', 'وقود وكروت بنزين واستهلاك قدام المستهدف', 'فواتير بالقيمة المضافة والخصم من المنبع وكشف حساب وأعمار ديون', 'صيانة وقطع غيار وكاوتش', 'مكسب كل رحلة وعربية وعميل وخط، وخريطة وبوابة للعميل'],
                'faq' => [
                    ['q' => 'بيتربط بأي جهاز تتبع؟', 'a' => 'النظام فيه نقطة ربط موثقة، وشركة التتبع هي اللي بتبعتله المواقع. مبيسحبش المواقع من أنظمة الشركات، ومفيش تخطيط مسارات ولا حساب وقت الوصول من الخريطة.'],
                    ['q' => 'بيبعت الفاتورة الإلكترونية للضرائب؟', 'a' => 'لا. فاتورة النقل للشركات فاتورة إلكترونية بين شركات، بتتعمل من نظام الحسابات (زي نظام إدارة العملاء والحسابات عندنا). والنسب بتحددها انت، فاسأل محاسبك.'],
                    ['q' => 'فيه حسابات عامة ومرتبات؟', 'a' => 'لا. مفيش دفتر أستاذ ولا مرتبات ولا تسوية بنكية ولا كشوف حساب للموردين. يومية السواق مصروف على الرحلة.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: العميل بيوصله إن الشحنة اتحملت وخرجت واتسلمت برابط لإثبات التسليم، والفاتورة وتذكير المتأخر برابط دفع، والسواق بيوصله رحلته، ومدير الأسطول بيوصله الأوراق اللي هتخلص. رسايل العميل مفيهاش تكلفة ولا ربح. من غير حساب كل رسالة بتفتح واتساب جاهزة.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل العربيات والسواقين والعملاء في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تعرف كل عربية بتكسب كام؟',
            ] + SystemsCatalogSaas::extras('ar', 'فاتورة أو رصيده كله'),
            'en' => [
                'kicker' => 'Transport and distribution companies', 'headline' => 'What every trip earned, and what every vehicle cost.',
                'subheadline' => 'A trip moves from planned to loaded, on the way and delivered. The driver updates it on the phone and photographs the signed waybill, fuel, tolls and driver cash are charged to the trip, and the invoice comes from delivered trips.',
                'who' => 'Trucking and logistics companies, distribution fleets, school buses and microbus operators, and small car rental.',
                'problems_title' => 'Why does a transport company not know where it makes money?',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'Unknown trip profit', 'body' => 'Fuel, tolls, allowances and loading are paid without being tied to the trip and the customer.'],
                    ['icon' => 'file', 'title' => 'Licences and insurance expire', 'body' => 'A vehicle licence, insurance or driving licence expires and nobody notices until the vehicle is stopped.'],
                    ['icon' => 'zap', 'title' => 'Lost fuel', 'body' => 'More litres than usual or an odometer that did not move, and nobody compares use with the target.'],
                ],
                'showcase_title' => 'The trip, the map and the driver screen', 'showcase' => [
                    ['title' => 'The trip page', 'body' => 'Customer, loading, delivery, cargo, vehicle, driver and price, with revenue, fuel, expenses and profit, and an A4 waybill.'],
                    ['title' => 'The map', 'body' => 'The last position of every vehicle from its tracker on a map, and the vehicles whose position is late.'],
                    ['title' => 'The driver screen', 'body' => 'My trips, one button for the next step, report a delay, proof of delivery with photo and signature, expenses, fuel and cash.'],
                ],
                'pillars_title' => 'Four things that protect the fleet',
                'pillars' => [
                    ['icon' => 'shield', 'title' => 'No loading', 'body' => 'A vehicle with an expired licence or insurance or in the workshop, and a driver with an expired licence, cannot be loaded.'],
                    ['icon' => 'zap', 'title' => 'Fuel use', 'body' => 'Litres per 100 km between fill-ups against each vehicle\'s target, and any fill-up above the tolerance is flagged.'],
                    ['icon' => 'wrench', 'title' => 'Maintenance, parts and tyres', 'body' => 'Preventive plans by km or days, work orders with parts from the store, and tyres by serial number with their km.'],
                    ['icon' => 'calculator', 'title' => 'Invoices and profit', 'body' => 'Invoices from delivered trips with VAT and withholding tax, statements, and profit per trip, vehicle, customer and route.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the fleet system',
                    'before' => [['b' => 'Trips', 't' => 'Calling the driver to find out where he is.'], ['b' => 'Fuel', 't' => 'Station receipts in a drawer.'], ['b' => 'Papers', 't' => 'Licences that expire by surprise.']],
                    'after' => [['b' => 'Trips', 't' => 'The driver updates the trip on the phone.'], ['b' => 'Fuel', 't' => 'Each vehicle\'s use against its target.'], ['b' => 'Papers', 't' => 'A list of expired and expiring papers, and no loading.']]],
                'metrics' => [['value' => '5', 'label' => 'Trip steps from planned to proof of delivery received'], ['value' => '6', 'label' => 'Ready roles: owner, fleet manager, dispatcher, accountant, maintenance supervisor and storekeeper'], ['value' => '126', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs the fleet of a transport company: vehicles with their Egyptian plate, type, capacity and ownership (owned, leased or a third-party carrier), their papers, drivers with the licence grade that decides what they may drive, and customers with a price per route.', 'Inside: trips with their steps, the waybill and proof of delivery, trip expenses, driver cash and its settlement, fuel and fuel cards, invoices, receipts and aging, maintenance, a parts store and tyres, profitability, driver screens on the phone, a customer portal to follow shipments, and an integration point for GPS trackers. One installation serves several companies, and each company sees only its own data. It ships with a year of fictional sample data.'],
                'list' => ['Vehicles with Egyptian plates and papers, with expiry warnings', 'Drivers by licence grade, violations and a performance score', 'Customers with prices per route by trip, ton, km or day', 'Trips with steps, an A4 waybill and proof of delivery with photo and signature', 'Trip expenses and driver cash with settlement', 'Fuel, fuel cards and use against target', 'Invoices with VAT and withholding tax, statements and aging', 'Maintenance, spare parts and tyres', 'Profit per trip, vehicle, customer and route, a map and a customer portal'],
                'faq' => [
                    ['q' => 'Does it connect to any GPS tracker?', 'a' => 'The system has a documented integration point, and the tracking company pushes positions to it. It does not fetch positions from vendor systems, and there is no route planning or arrival estimate from the map.'],
                    ['q' => 'Does it send e-invoices to the tax authority?', 'a' => 'No. Freight invoices to companies are business-to-business e-invoices, issued from the accounting system (such as our CRM and accounting system). You set the tax rates, so ask your accountant.'],
                    ['q' => 'Is there a general ledger and payroll?', 'a' => 'No. There is no general ledger, payroll, bank reconciliation or supplier statements. Driver allowances are trip costs.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: the customer is told when the shipment is loaded, leaves and is delivered, with a link to the proof of delivery; invoices and overdue reminders carry a payment link; drivers get their trip; and the fleet manager gets the papers about to expire. Customer messages never carry a cost or profit. Without an account every message opens WhatsApp ready to send.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All vehicles, drivers and customers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to know what every vehicle earns?',
            ] + SystemsCatalogSaas::extras('en', 'an invoice or the whole balance'),
        ];
    }
}
