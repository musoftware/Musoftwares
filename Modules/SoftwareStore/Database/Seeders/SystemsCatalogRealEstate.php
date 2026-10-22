<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Real estate office system. Same shape as the systems in SystemsCatalog (hooked in with
 * `...SystemsCatalogExtra::systems(), SystemsCatalogRealEstate::system()`).
 * The facts come from the system's README, seeder and test suite. Limits are stated on purpose:
 * the contract is only a draft template and not legal advice, no property portal sync, no tax e-receipt connector,
 * no online payment, no automatic messages, demo data is fake.
 */
class SystemsCatalogRealEstate
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'real-estate-system', 'colors' => ['accent' => '#4f46e5', 'ink' => '#14123d'],
            'name_en' => 'Real Estate Office System', 'name_ar' => 'نظام مكتب العقارات',
            'tagline_en' => 'Properties, clients, viewings, deals, rental contracts, monthly rent and commissions for a real estate office.',
            'tagline_ar' => 'عقارات وعملاء ومعاينات وصفقات وعقود إيجار وإيجار شهري وعمولات لمكتب عقارات.',
            'ar' => [
                'kicker' => 'مكاتب العقارات', 'headline' => 'مكتبك العقاري كله في مكان واحد، من أول مكالمة لحد تحصيل الإيجار.',
                'subheadline' => 'العقارات والملاك والعملاء والمعاينات والصفقات وعقود الإيجار وجدول الإيجار الشهري والعمولات، وكل مندوب يشوف شغله بس.',
                'who' => 'مكاتب العقارات والسماسرة اللي بيبيعوا ويأجروا ومحتاجين يتابعوا العملاء والصفقات وتحصيل الإيجارات.',
                'problems_title' => 'ليه مكتب العقارات بيضيع منه شغل؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'عملاء في الواتساب والدفتر', 'body' => 'العميل بيسأل وبعد أسبوع محدش فاكر مين كلمه ولا إيه اتقال.'],
                    ['icon' => 'clock', 'title' => 'إيجار بيتأخر من غير ما حد ياخد باله', 'body' => 'مفيش قائمة واضحة بمين دفع ومين متأخر، والعقد بينتهي فجأة.'],
                    ['icon' => 'calculator', 'title' => 'عمولات بتتحسب بالراس', 'body' => 'نصيب المندوب ونصيب المكتب بيتحسبوا آخر الشهر وبيحصل خلاف.'],
                ],
                'showcase_title' => 'العقارات والصفقات والإيجار', 'showcase' => [
                    ['title' => 'العقارات بالصور', 'body' => 'شقق وفيلات ومحلات ومكاتب وأراضي ومخازن، للبيع أو للإيجار، بالمساحة والدور والسعر وصور لكل عقار.'],
                    ['title' => 'الصفقات والمعاينات', 'body' => 'كل صفقة بتتحرك من استفسار لمعاينة لتفاوض لحجز لنجاح أو خسارة، وجدول أسبوعي للمعاينات مع تذكير بالمتابعات.'],
                    ['title' => 'الإيجار والعقود', 'body' => 'العقد بيعمل جدول الإيجار الشهري لوحده، والتحصيل كامل أو جزئي، وإيصال مرقّم، وقائمة بالمتأخر مع رسالة واتساب جاهزة.'],
                ],
                'pillars_title' => 'أربع حاجات تنظم المكتب',
                'pillars' => [
                    ['icon' => 'users', 'title' => 'عملاء ومتابعات', 'body' => 'كل عميل له مصدر وميزانية ومندوب، وكل متابعة ليها تاريخ تذكير بيظهر في الصفحة الرئيسية.'],
                    ['icon' => 'check', 'title' => 'الصفقة تحجز العقار', 'body' => 'الحجز بيمنع عرض نفس العقار لحد تاني، والبيع بيقفله، والخسارة بترجعه متاح.'],
                    ['icon' => 'chart', 'title' => 'عمولات المندوبين', 'body' => 'عمولة المكتب ونصيب المندوب لكل صفقة، وتقرير بالمندوب في أي فترة.'],
                    ['icon' => 'shield', 'title' => 'صلاحيات وصور خاصة', 'body' => 'مندوب ومحاسب ومدير، وصور العقارات محفوظة بشكل خاص ومتتفتحش إلا لمستخدمي نفس المكتب.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام المكتب',
                    'before' => [['b' => 'العملاء', 't' => 'أرقام في الموبايل ومحدش فاكر آخر مكالمة.'], ['b' => 'الإيجار', 't' => 'دفتر بيتنسى فيه المتأخر.'], ['b' => 'العمولة', 't' => 'حسابات آخر الشهر بالراس.']],
                    'after' => [['b' => 'العملاء', 't' => 'ملف لكل عميل ومتابعات بتذكّرك.'], ['b' => 'الإيجار', 't' => 'قائمة المتأخر ورسالة واتساب جاهزة.'], ['b' => 'العمولة', 't' => 'تقرير بنصيب كل مندوب.']]],
                'metrics' => [['value' => '30', 'label' => 'عقار تجريبي في النسخة التجريبية'], ['value' => '3', 'label' => 'أحجام طباعة للإيصال'], ['value' => '87', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيحفظ العقارات والملاك والعملاء والمعاينات والصفقات وعقود الإيجار. أول ما تعمل عقد إيجار بيتعمل جدول بدفعة لكل شهر، وأقدم شهر غير مدفوع هو اللي بيتسدد أول لما تستلم مبلغ، وممكن تستلم جزء من الشهر.', 'جوه النظام: العقارات وصورها، والملاك، والعملاء والمتابعات، وجدول المعاينات، والصفقات والعمولات، وعقود الإيجار وجدولها، والإيجارات المتأخرة، والعقود اللي بتنتهي قريبًا، والتقارير، وطباعة الإيصالات ومسودة عقد إيجار بالعربي أو الإنجليزي. فيه بيانات تجريبية خيالية عشان تجرب قبل ما تبدأ.'],
                'list' => ['عقارات للبيع والإيجار بالمساحة والدور والسعر والحالة وبحث وفلاتر', 'صور لكل عقار (حتى 12) بفحص حقيقي للملف وحفظ خاص', 'ملاك العقارات وعقاراتهم', 'عملاء ومتابعات بتاريخ تذكير', 'جدول معاينات أسبوعي', 'صفقات بيع وإيجار بمراحل وعمولة المكتب ونصيب المندوب', 'عقود إيجار بجدول شهري وتحصيل كامل أو جزئي وإيصالات مرقمة 80 و58 مم وA4', 'قائمة الإيجارات المتأخرة مع رسالة واتساب جاهزة (بتفتح على جهازك، والنظام مبيبعتش)', 'قائمة العقود اللي بتنتهي خلال 60 يوم', 'تقارير العمولات والإيجار المحصّل ونسبة الإشغال', 'مسودة عقد إيجار بالعربي والإنجليزي (نموذج للمراجعة القانونية)', 'صلاحيات للمدير والمندوب والمحاسب'],
                'faq' => [
                    ['q' => 'عقد الإيجار اللي بيطبعه النظام عقد قانوني؟', 'a' => 'لا. هو مسودة نموذجية بس، وليست استشارة قانونية ولا عقدًا موثقًا أو مسجلًا. سبنا شروط التأخر في السداد ومدة الإخطار والمحكمة المختصة والقانون الواجب التطبيق فاضية عشان محاميك يكملها. لازم محامٍ يراجعها قبل ما أي طرف يوقّع.'],
                    ['q' => 'بينشر العقارات على مواقع العقارات؟', 'a' => 'لا. مفيش ربط مع مواقع إعلانات العقارات، لا للنشر ولا للاستيراد. العقارات والصور بتفضل جوه نظامك.'],
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب (ETA)؟', 'a' => 'لا. الإيصالات اللي بيطبعها النظام إيصالات مكتب، ومبيبعتش حاجة لمصلحة الضرائب. اسأل محاسبك لو نشاطك مطلوب منه فواتير أو إيصالات إلكترونية.'],
                    ['q' => 'بيبعت رسائل واتساب لوحده؟', 'a' => 'لا. زر واتساب بيفتح رسالة جاهزة على جهازك وإنت اللي بتبعتها.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل العقارات والأسماء والأرقام والصور في النسخة التجريبية خيالية، والصور مكتوب عليها إنها صور تجريبية.'],
                    ['q' => 'بيانات العملاء وصورهم محمية؟', 'a' => 'الصور محفوظة بشكل خاص ومتتفتحش إلا لمستخدمي نفس المكتب، وكل مكتب بيشوف بياناته بس. بيانات العملاء بيانات شخصية والمكتب مسؤول عن حمايتها وعن التزاماته القانونية. النظام لوحده مبيخليش المكتب ملتزم قانونيًا.'],
                ],
                'cta_title' => 'جاهز تنظم مكتبك العقاري؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'تسجيل الدفعات يدويًا: نقدي وبطاقة وتحويل ومحفظة (من غير دفع أونلاين)'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. الموظف بيسجل الدفعة وطريقتها (نقدي أو بطاقة أو تحويل أو محفظة)، لكن النظام مبيحصلش أي فلوس بنفسه.'],
            ],
            'en' => [
                'kicker' => 'Real estate offices', 'headline' => 'Your whole real estate office in one place, from the first call to collecting the rent.',
                'subheadline' => 'Properties, owners, clients, viewings, deals, rental contracts, the monthly rent schedule and commissions, and every agent sees only their own work.',
                'who' => 'Real estate offices and brokers who sell and rent, and need to follow clients, deals and rent collection.',
                'problems_title' => 'Why does a real estate office lose work?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Clients live in WhatsApp and a notebook', 'body' => 'A client asks, and a week later nobody remembers who called or what was said.'],
                    ['icon' => 'clock', 'title' => 'Rent runs late and nobody notices', 'body' => 'There is no clear list of who paid and who is late, and a contract ends by surprise.'],
                    ['icon' => 'calculator', 'title' => 'Commissions worked out in someone\'s head', 'body' => 'The agent share and the office share are calculated at month end and cause arguments.'],
                ],
                'showcase_title' => 'Properties, deals and rent', 'showcase' => [
                    ['title' => 'Properties with photos', 'body' => 'Apartments, villas, shops, offices, land and warehouses, for sale or for rent, with area, floor, price and photos for each.'],
                    ['title' => 'Deals and viewings', 'body' => 'Every deal moves from inquiry to viewing, negotiation, reserved, then won or lost, with a weekly viewings calendar and follow-up reminders.'],
                    ['title' => 'Rent and contracts', 'body' => 'A contract builds its monthly rent schedule by itself, with full or part payments, numbered receipts, and a late list with a ready WhatsApp message.'],
                ],
                'pillars_title' => 'Four things that organize the office',
                'pillars' => [
                    ['icon' => 'users', 'title' => 'Clients and follow-ups', 'body' => 'Each client has a source, a budget and an agent, and each follow-up has a reminder date that shows on the dashboard.'],
                    ['icon' => 'check', 'title' => 'A deal holds the property', 'body' => 'Reserved stops the same property from being offered to someone else, a sale closes it, and a lost deal frees it.'],
                    ['icon' => 'chart', 'title' => 'Agent commissions', 'body' => 'The office commission and the agent share for every deal, and a report by agent for any dates.'],
                    ['icon' => 'shield', 'title' => 'Roles and private photos', 'body' => 'Agent, accountant and manager. Property photos are stored privately and open only for users of the same office.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the office system',
                    'before' => [['b' => 'Clients', 't' => 'Numbers in a phone and nobody remembers the last call.'], ['b' => 'Rent', 't' => 'A notebook where the late ones are forgotten.'], ['b' => 'Commission', 't' => 'Month-end sums in your head.']],
                    'after' => [['b' => 'Clients', 't' => 'A file per client and follow-ups that remind you.'], ['b' => 'Rent', 't' => 'A late list and a ready WhatsApp message.'], ['b' => 'Commission', 't' => 'A report with each agent\'s share.']]],
                'metrics' => [['value' => '30', 'label' => 'Sample properties in the demo'], ['value' => '3', 'label' => 'Receipt print sizes'], ['value' => '87', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system keeps properties, owners, clients, viewings, deals and rental contracts. When you create a rental contract it builds a schedule with one payment for every month. When you receive money, the oldest unpaid month is paid first, and you can take part of a month.', 'Inside: properties and their photos, owners, clients and follow-ups, a viewings calendar, deals and commissions, rental contracts and their schedule, late rent, contracts ending soon, reports, receipt printing and a draft rental contract in Arabic or English. It ships with fictional sample data so you can try it first.'],
                'list' => ['Properties for sale and rent with area, floor, price, status, search and filters', 'Up to 12 photos per property, with a real file check and private storage', 'Property owners and their properties', 'Clients and follow-ups with reminder dates', 'A weekly viewings calendar', 'Sale and rent deals with stages, the office commission and the agent share', 'Rental contracts with a monthly schedule, full or part payments and numbered receipts on 80 mm, 58 mm and A4', 'A late rent list with a ready WhatsApp message (it opens on your device, the system sends nothing)', 'A list of contracts ending within 60 days', 'Reports for commissions, rent collected and occupancy', 'A draft rental contract in Arabic and English (a template for legal review)', 'Roles for manager, agent and accountant'],
                'faq' => [
                    ['q' => 'Is the rental contract the system prints a legal contract?', 'a' => 'No. It is only a draft template: it is not legal advice and not a notarised or registered contract. We left the late-payment terms, the notice period, the competent court and the applicable law blank for your lawyer to complete. A lawyer must review it before anyone signs.'],
                    ['q' => 'Does it publish properties on property portals?', 'a' => 'No. There is no connection to property listing sites, neither to publish nor to import. Properties and photos stay inside your system.'],
                    ['q' => 'Does it support the tax e-receipt (ETA)?', 'a' => 'No. The receipts it prints are office receipts, and it sends nothing to the tax authority. Ask your accountant whether your business must issue e-invoices or e-receipts.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'No. The WhatsApp button opens a ready message on your device and you send it yourself.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All properties, names, numbers and pictures in the demo are fictional, and the pictures say they are demo pictures.'],
                    ['q' => 'Are client data and photos protected?', 'a' => 'Photos are stored privately and open only for users of the same office, and each office sees only its own data. Client details are personal data, and the office is responsible for protecting them and for its own legal duties. The software alone does not make an office compliant.'],
                ],
                'cta_title' => 'Ready to organize your real estate office?',
                'included' => ['Arabic and English with right-to-left support', 'Payments are recorded by staff: cash, card, transfer and wallet (no online payment)'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. Staff record the payment and its method (cash, card, transfer or wallet), but the system does not collect any money itself.'],
            ],
        ];
    }
}
