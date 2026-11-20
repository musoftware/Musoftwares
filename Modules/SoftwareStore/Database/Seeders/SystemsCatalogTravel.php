<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Travel company CRM, bookings and accounting. Same shape as the systems in SystemsCatalog (listed in SystemsCatalogSaas).
 * The facts come from the system's README, seeders and test suite. Limits are stated on purpose: no airline, hotel or GDS
 * connection, no live ETA connector (status tracked by hand), the special tourism VAT is not automated, ad spend is typed in,
 * demo data is fake.
 */
class SystemsCatalogTravel
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'travel-agency-system', 'colors' => ['accent' => '#0369a1', 'ink' => '#062033'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'Travel Company CRM and Accounting System', 'name_ar' => 'نظام شركات السياحة: عملاء وحجوزات وحسابات',
            'tagline_en' => 'Leads from every ad and channel, bookings, travellers, visas and supplier bills, with full Egyptian accounting, for a travel company.',
            'tagline_ar' => 'ليدز من كل إعلان وقناة، وحجوزات ومسافرين وتأشيرات وفواتير موردين، مع حسابات كاملة على النظام المصري لشركة سياحة.',
            'ar' => [
                'kicker' => 'شركات السياحة والعمرة والحج', 'headline' => 'كل عميل عارفين جه منين، وكل جنيه عارفين راح فين.',
                'subheadline' => 'الليد بيدخل من فيسبوك أو واتساب أو كود QR على إعلان، بيتوزع على السيلز ويتحسب وقت الرد عليه، ولما يحجز الحجز بيعمل قيوده في الحسابات لوحده لحد مكسب كل رحلة.',
                'who' => 'شركات السياحة اللي بتبيع عمرة وحج ورحلات داخلية وخارجية وتذاكر وتأشيرات، وعندها سيلز وعمليات ومحاسب، في فرع واحد أو أكتر.',
                'problems_title' => 'ليه شركة السياحة بتخسر عملاء وفلوس من غير ما تحس؟',
                'problems' => [
                    ['icon' => 'message', 'title' => 'ليدز بتضيع', 'body' => 'رسايل من فيسبوك وواتساب ومكالمات، ومحدش عارف مين رد ومين اتأخر وأنهي إعلان جاب الحجز.'],
                    ['icon' => 'users', 'title' => 'مسافرين وتأشيرات في شيتات', 'body' => 'كشف الغرف والأتوبيس والجوازات والتأشيرات متفرقين، وجواز منتهي بيتكشف قبل السفر بيوم.'],
                    ['icon' => 'calculator', 'title' => 'مكسب الرحلة مش واضح', 'body' => 'الفندق والطيران والنقل والعمولات بتتحسب في الآخر، ومحدش عارف الرحلة كسبت ولا خسرت.'],
                ],
                'showcase_title' => 'من الليد للحجز للقيد', 'showcase' => [
                    ['title' => 'ليدز ومراحل البيع', 'body' => 'الليد بيدخل من الفورم أو واتساب أو إعلانات ميتا أو كود QR، بيتوزع لوحده، ويحمر لو عدى 5 دقايق من غير رد، ولوحة بمراحل البيع لحد الحجز.'],
                    ['title' => 'صفحة الحجز', 'body' => 'الأفراد والغرف وتذاكر الطيران والأقساط والمدفوع والمتبقي، وفاوتشر للعميل وللمورد، ورابط دفع أونلاين للقسط.'],
                    ['title' => 'مكسب كل رحلة', 'body' => 'الإيراد ناقص الفندق والطيران والنقل والتأشيرات والمرشدين والعمولة، ومعاه ميزان مراجعة وقائمة دخل وميزانية.'],
                ],
                'pillars_title' => 'أربع حاجات بتفرق مع شركة السياحة',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'وقت الرد محسوب', 'body' => 'أحمر بعد 5 دقايق، وتنبيه للمدير بعد 15، والليد بيروح لحد تاني بعد 30. والأوقات دي بتتغير من الإعدادات.'],
                    ['icon' => 'chart', 'title' => 'مصدر كل حجز', 'body' => 'الحملات بمصروفها، وتكلفة الليد والحجز والعائد على الإعلان، وكود QR لكل يافطة أو بروشور أو سيلز.'],
                    ['icon' => 'calculator', 'title' => 'حسابات على النظام المصري', 'body' => 'كل حدث في الحجز بيعمل قيده: المقدم على حساب العملاء، والإيراد يوم تنفيذ الرحلة، وفروق العملة والخصم والإضافة والقيمة المضافة.'],
                    ['icon' => 'users', 'title' => 'حضور ومرتبات', 'body' => 'حضور يومي، وتأمينات وضريبة مرتبات بالشرايح، وعمولة السيلز من الحجوزات اللي اتنفذت، وقيد المرتبات لوحده.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام شركات السياحة',
                    'before' => [['b' => 'الليدز', 't' => 'رسايل متفرقة ومحدش عارف مين اتأخر.'], ['b' => 'المسافرين', 't' => 'شيتات للغرف والأتوبيس والتأشيرات.'], ['b' => 'الحسابات', 't' => 'محاسب بيكتب القيود بإيده آخر الشهر.']],
                    'after' => [['b' => 'الليدز', 't' => 'كل ليد ليه مصدر وسيلز ووقت رد.'], ['b' => 'المسافرين', 't' => 'كشف غرف وأتوبيس وتأشيرات من صفحة الرحلة.'], ['b' => 'الحسابات', 't' => 'القيود بتتعمل لوحدها ومكسب كل رحلة جاهز.']]],
                'metrics' => [['value' => '8', 'label' => 'مراحل للبيع من ليد جديد لحد التقييم'], ['value' => '5', 'label' => 'أدوار جاهزة: المالك والسيلز ومدير المبيعات والعمليات والمحاسب'], ['value' => '210', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيجمع إدارة العملاء والحجوزات والحسابات لشركة سياحة في مكان واحد. الليد بيدخل من أي قناة، ورقم الموبايل المصري بيتوحد عشان العميل يفضل ملف واحد، والمنتج بيتعرف من الكود أو الكلام (عمرة، حج، تركيا...)، والليد بياخد نقط ويتوزع على السيلز.', 'جوه النظام: الرحلات والباكدجات بأسعار لكل نوع غرفة، والحصص من الفنادق والطيران، والحجوزات والمسافرين والتأشيرات، والمدفوعات والأقساط والشيكات، والموردين بعملتهم، وشجرة حسابات وقيود ومصروفات وعهد وأصول وتسوية بنكية ومرتبات وإقفال السنة، وبوابة للعميل يشوف فيها حجزه. نسخة واحدة بتخدم أكتر من شركة، وكل شركة بتشوف بياناتها بس. فيه بيانات تجريبية خيالية.'],
                'list' => ['ليدز من الفورم وواتساب وإعلانات ميتا وأي ويب هوك، مع حفظ الحملة والإعلان', 'كود QR لكل إعلان أو فرع أو سيلز بيفتح واتساب برسالة جاهزة', 'توزيع تلقائي ووقت رد محسوب ولوحة بمراحل البيع', 'عروض أسعار برابط للعميل وطباعة A4', 'رحلات وحصص فنادق وطيران وكشف غرف وأتوبيس وتأشيرات', 'حجوزات بأقساط وعملات وفاوتشر للعميل وللمورد', 'قيود تلقائية وميزان مراجعة وقائمة دخل وميزانية وإقرار القيمة المضافة', 'حضور ومرتبات وتأمينات وضريبة مرتبات', 'بوابة للعميل برقم الموبايل ورقم الحجز'],
                'faq' => [
                    ['q' => 'بيتربط مع شركات الطيران أو الفنادق؟', 'a' => 'لا. مفيش ربط مع أنظمة الحجز العالمية ولا مواقع الفنادق. الأسعار والحصص والتذاكر بتتكتب بإيدك.'],
                    ['q' => 'بيبعت الفاتورة الإلكترونية للضرائب؟', 'a' => 'لا. النظام بيدي رقم فاتورة ضريبية وبيتابع حالتها عند المصلحة (ETA) بإيدك بس، ومفيش ربط مباشر مع مصلحة الضرائب. اسأل محاسبك.'],
                    ['q' => 'بيحسب القيمة المضافة الخاصة بالسياحة؟', 'a' => 'لا. المنتجات اللي بتختارها بتتحسب عليها القيمة المضافة على سعرها كله. المعاملة الخاصة بخدمات السياحة (هامش الربح والعمرة والحج) مش متعملة لوحدها، فاتأكد من الإعداد مع مستشارك الضريبي.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: الرسالة من رقم جديد بتبقى ليد، وتذكير السفر بيتبعت لوحده قبل الرحلة بيوم لتلات أيام، والفاوتشر وتذكير القسط بيتبعتوا من الحجز. من غير حساب كل رسالة بتفتح واتساب جاهزة والموظف يدوس إرسال. مفيش حملات جماعية ولا بوت.'],
                    ['q' => 'بيسحب مصروف الإعلانات من ميتا لوحده؟', 'a' => 'لا. مصروف الحملة بيتكتب بإيدك، وليد ميتا لازم يوصل ببياناته في الويب هوك.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل العملاء والأسماء وأرقام التليفونات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تعرف كل حجز جه منين وكسب كام؟',
            ] + SystemsCatalogSaas::extras('ar', 'رصيد الحجز أو قسط منه'),
            'en' => [
                'kicker' => 'Travel, Umrah and Hajj companies', 'headline' => 'Every customer: we know where they came from. Every pound: we know where it went.',
                'subheadline' => 'A lead comes from Facebook, WhatsApp or a QR code on an ad, goes to a sales person and the reply time is counted. When it books, the booking posts its own entries in the books, all the way to the profit of each trip.',
                'who' => 'Travel companies that sell Umrah, Hajj, domestic and outbound trips, tickets and visas, with sales, operations and an accountant, in one branch or more.',
                'problems_title' => 'Why does a travel company lose customers and money without noticing?',
                'problems' => [
                    ['icon' => 'message', 'title' => 'Lost leads', 'body' => 'Messages from Facebook and WhatsApp and calls, and nobody knows who replied, who is late or which ad brought the booking.'],
                    ['icon' => 'users', 'title' => 'Travellers and visas in sheets', 'body' => 'Rooming, bus seats, passports and visas are scattered, and an expired passport is found the day before travel.'],
                    ['icon' => 'calculator', 'title' => 'Unclear trip profit', 'body' => 'Hotel, flights, transport and commissions are added up at the end, so nobody knows if the trip made money.'],
                ],
                'showcase_title' => 'From lead to booking to entry', 'showcase' => [
                    ['title' => 'Leads and the sales pipeline', 'body' => 'A lead arrives from the form, WhatsApp, Meta lead ads or a QR code, is assigned by itself, turns red after 5 minutes without a reply, and moves on a board until it books.'],
                    ['title' => 'The booking page', 'body' => 'Travellers, rooms, flight tickets, installments, paid and owed, a voucher for the customer and the supplier, and an online payment link for an installment.'],
                    ['title' => 'Profit of every trip', 'body' => 'Revenue minus hotel, flights, transport, visas, guides and commission, next to the trial balance, income statement and balance sheet.'],
                ],
                'pillars_title' => 'Four things that matter to a travel company',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Reply time is counted', 'body' => 'Red after 5 minutes, the manager is alerted after 15, and the lead moves to someone else after 30. All three times are settings.'],
                    ['icon' => 'chart', 'title' => 'The source of every booking', 'body' => 'Campaigns with their spend, cost per lead and per booking, return on ad spend, and a QR code for each sign, brochure or sales person.'],
                    ['icon' => 'calculator', 'title' => 'Egyptian accounting', 'body' => 'Every booking event posts its entry: deposits are customer advances, revenue is recognised when the trip is carried out, with exchange differences, withholding tax and VAT.'],
                    ['icon' => 'users', 'title' => 'Attendance and payroll', 'body' => 'A daily attendance sheet, social insurance and salary tax by the brackets, sales commission from carried-out bookings, and the payroll entry posted by itself.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the travel system',
                    'before' => [['b' => 'Leads', 't' => 'Scattered messages, nobody knows who is late.'], ['b' => 'Travellers', 't' => 'Sheets for rooms, buses and visas.'], ['b' => 'Accounts', 't' => 'The accountant types entries at month end.']],
                    'after' => [['b' => 'Leads', 't' => 'Every lead has a source, a sales person and a reply time.'], ['b' => 'Travellers', 't' => 'Rooming, bus and visa lists from the trip page.'], ['b' => 'Accounts', 't' => 'Entries post by themselves and trip profit is ready.']]],
                'metrics' => [['value' => '8', 'label' => 'Pipeline stages from new lead to review'], ['value' => '5', 'label' => 'Ready roles: owner, sales, sales manager, operations and accountant'], ['value' => '210', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system brings customer management, bookings and accounting for a travel company into one place. A lead comes from any channel, Egyptian mobile numbers are normalised so a customer stays one file, the product is guessed from the code or the words (Umrah, Hajj, Turkey...), and the lead is scored and assigned.', 'Inside: trips and packages with prices per room type, hotel and flight allotments, bookings, travellers and visas, payments, installments and cheques, suppliers in their own currency, a chart of accounts with entries, expenses, custodies, fixed assets, bank reconciliation, payroll and year-end closing, and a portal where the customer sees their booking. One installation serves several companies, and each company sees only its own data. It ships with fictional sample data.'],
                'list' => ['Leads from the website form, WhatsApp, Meta lead ads and any webhook, keeping the campaign and the ad', 'A QR code per ad, branch or sales person that opens WhatsApp with a ready message', 'Automatic assignment, counted reply time and a pipeline board', 'Price offers with a link for the customer and A4 print', 'Trips, hotel and flight allotments, rooming, bus and visa lists', 'Bookings with installments, currencies and vouchers for the customer and the supplier', 'Automatic entries, trial balance, income statement, balance sheet and the VAT return', 'Attendance, payroll, social insurance and salary tax', 'A customer portal with the phone number and booking number'],
                'faq' => [
                    ['q' => 'Does it connect to airlines or hotels?', 'a' => 'No. There is no connection to global booking systems or hotel channel managers. Prices, allotments and tickets are typed in.'],
                    ['q' => 'Does it send e-invoices to the tax authority?', 'a' => 'No. The system gives a tax invoice number and you track its ETA status by hand. There is no direct connection to the Egyptian Tax Authority. Ask your accountant.'],
                    ['q' => 'Does it apply the special VAT rules for tourism?', 'a' => 'No. The products you choose carry VAT on their whole price. The special treatment of tourism services (margin scheme, Umrah, Hajj) is not automated, so confirm the setup with your tax adviser.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: a message from a new number becomes a lead, the trip reminder goes out by itself 1 to 3 days before travel, and the voucher and installment reminder are sent from the booking. Without an account every message opens WhatsApp ready to send and a staff member presses send. No bulk campaigns and no bot.'],
                    ['q' => 'Does it import ad spend from Meta?', 'a' => 'No. Campaign spend is typed in, and a Meta lead must arrive with its details in the webhook.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All customers, names and phone numbers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to know where every booking came from and what it earned?',
            ] + SystemsCatalogSaas::extras('en', 'the balance of a booking or one installment'),
        ];
    }
}
