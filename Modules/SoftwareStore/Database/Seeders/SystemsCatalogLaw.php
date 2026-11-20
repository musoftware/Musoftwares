<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Law office system. Same shape as the systems in SystemsCatalog (listed in SystemsCatalogSaas).
 * The facts come from the system's README, seeders and test suite. Limits are stated on purpose: the appeal period table
 * must be checked by a practising lawyer, no court website connection, no withholding tax or B2B e-invoice, the e-receipt
 * is untested against the real ETA, demo data is fake.
 */
class SystemsCatalogLaw
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'law-office-system', 'colors' => ['accent' => '#1e3a8a', 'ink' => '#0b1530'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'Law Office Management System', 'name_ar' => 'نظام إدارة مكاتب المحاماة',
            'tagline_en' => 'Cases at every degree, the hearings roll, appeal deadlines, powers of attorney, fees, advances and a client portal for a law office.',
            'tagline_ar' => 'قضايا بكل درجاتها ورول الجلسات ومواعيد الطعن والتوكيلات والأتعاب والأمانات وبوابة للموكل لمكتب محاماة.',
            'ar' => [
                'kicker' => 'مكاتب المحاماة', 'headline' => 'جلسات بكرة جاهزة، ومواعيد الطعن محسوبة، وأتعاب كل قضية واضحة.',
                'subheadline' => 'كل قضية ليها ملف بالجلسات والقرارات والخصوم والتوكيل، ولما الحكم يتسجل مواعيد الاستئناف والنقض بتتحسب، والرول بيتطبع لكل محكمة، والموكل بيشوف قضيته وحسابه من البوابة.',
                'who' => 'مكاتب المحاماة والمحامين اللي عندهم قضايا في محاكم مختلفة وفريق محامين وسكرتارية ومحاسب، وموكلين أفراد وشركات.',
                'problems_title' => 'ليه المكتب بيتعب في الجلسات والمواعيد؟',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'ميعاد طعن ممكن يفوت', 'body' => 'حساب 40 أو 60 يوم من الحكم مع الإجازات بيتعمل بالإيد، وغلطة يوم ممكن تضيع القضية.'],
                    ['icon' => 'users', 'title' => 'محامي في محكمتين في نفس الوقت', 'body' => 'الأجندة في دفتر، ومحدش بياخد باله من التعارض لحد يوم الجلسة.'],
                    ['icon' => 'calculator', 'title' => 'أتعاب وأمانات متلخبطة', 'body' => 'مين دفع مقدم ومين عليه، ومصروفات القضية اللي دفعها المكتب بتتنسي.'],
                ],
                'showcase_title' => 'القضية والرول والجلسات', 'showcase' => [
                    ['title' => 'ملف القضية', 'body' => 'الجلسات بقراراتها، والقضايا المرتبطة من أول درجة للاستئناف والنقض، والحكم وميعاد الطعن بالقانون والمادة.'],
                    ['title' => 'رول الجلسات', 'body' => 'جلسات اليوم متقسمة على المحاكم بالوقت والدائرة والموكل والخصم والمطلوب، وعمود فاضي للقرار، طباعة A4.'],
                    ['title' => 'الجلسات والتعارضات', 'body' => 'أجندة الجلسات، وتعارض المحامي في محكمتين متقاربين في الوقت، والجلسات اللي عدت من غير قرار.'],
                ],
                'pillars_title' => 'أربع حاجات بتحمي المكتب',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'مواعيد طعن محسوبة', 'body' => 'جدول مواعيد الطعن بيتعدل، وكل سطر مكتوب فيه القانون والمادة، ويوم الحكم مبيتحسبش، والإجازة بتنقل آخر يوم لأول يوم شغل.'],
                    ['icon' => 'file', 'title' => 'توكيلات ومذكرات', 'body' => 'التوكيل بنوعه ونطاقه وتاريخ انتهائه، والمذكرة كل رفع ليها نسخة جديدة والقديمة بتفضل.'],
                    ['icon' => 'calculator', 'title' => 'أتعاب بكل أنواعها', 'body' => 'مبلغ ثابت أو بالمرحلة أو بالجلسة أو نسبة من الحكم أو شهري للشركات، وفواتير ومصروفات قضايا وأمانات واستردادها.'],
                    ['icon' => 'lock', 'title' => 'سرية', 'body' => 'المحامي بيشوف قضاياه بس، والملفات الخاصة لكاتبها والمالك، والموكل بيشوف اللي اتشارك معاه بس.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام مكتب المحاماة',
                    'before' => [['b' => 'الجلسات', 't' => 'أجندة ورق ورول بيتكتب كل ليلة.'], ['b' => 'المواعيد', 't' => 'حساب الاستئناف والنقض بالإيد.'], ['b' => 'الأتعاب', 't' => 'دفتر للمقدمات والمصروفات.']],
                    'after' => [['b' => 'الجلسات', 't' => 'رول جاهز لكل محكمة وتنبيه بالتعارض.'], ['b' => 'المواعيد', 't' => 'ميعاد مقترح من جدول بالقانون والمادة.'], ['b' => 'الأتعاب', 't' => 'كشف حساب للموكل بالفواتير والأمانات.']]],
                'metrics' => [['value' => '6', 'label' => 'أدوار جاهزة: المالك والشريك والمحامي والمتدرب والسكرتير والمحاسب'], ['value' => '5', 'label' => 'أنواع لاتفاقات الأتعاب'], ['value' => '146', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير مكتب محاماة: الموكلين أفراد وشركات، والتوكيلات، والقضايا بنوعها (مدني وجنائي وأسرة وعمال وإداري واقتصادي) ودرجتها، والجلسات وقراراتها اللي بتعمل الجلسة الجاية لوحدها، والتنبيهات، ومواعيد الطعن، والمهام.', 'جوه النظام: المستندات والمذكرات بنسخها، والاستشارات، وسجل صياغة العقود، واتفاقات الأتعاب، والفواتير والإيصالات والأمانات، وكشف حساب وأعمار ديون، وسجل وقت المحامين وتقرير الإنتاجية، وبوابة للموكل. نسخة واحدة بتخدم أكتر من مكتب، وكل مكتب بيشوف بياناته بس. فيه بيانات تجريبية خيالية.'],
                'list' => ['موكلين أفراد وشركات وتوكيلات بتاريخ انتهاء', 'قضايا بكل الدرجات مربوطة ببعض من أول درجة للنقض', 'جلسات وقرارات بتعمل الجلسة الجاية لوحدها', 'تعارض المحامين في محكمتين ورول يومي للطباعة', 'مواعيد طعن من جدول قابل للتعديل ومهام', 'مستندات ومذكرات بنسخ وملفات خاصة', 'اتفاقات أتعاب بخمس أنواع وفواتير ومصروفات قضايا', 'إيصالات وأمانات وكشف حساب وأعمار ديون', 'سجل وقت وإنتاجية المحامين وبوابة للموكل'],
                'faq' => [
                    ['q' => 'جدول مواعيد الطعن مراجع قانونيًا؟', 'a' => 'لا. الجدول بداية مكتوبة من نصوص القوانين زي ما قريناها، ولازم محامي ممارس يراجع كل سطر قبل ما المكتب يعتمد عليه. مواعيد المسافة والمواعيد الخاصة مش بتتضاف لوحدها، والإجازات الدينية بيضيفها المكتب كل سنة.'],
                    ['q' => 'بيتربط بموقع المحاكم؟', 'a' => 'لا. أرقام القضايا والرول والقرارات بيكتبها المكتب.'],
                    ['q' => 'بيحسب الخصم والإضافة أو يبعت فاتورة إلكترونية للشركات؟', 'a' => 'لا. فيه إيصال إلكتروني للدفعات بس (خدمات مهنية)، متجرب على خادم تجريبي ومتجربش على مصلحة الضرائب الحقيقية. اسأل محاسبك.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: تذكير الموكل بجلسة بكرة كل ليلة، وميعاد الجلسة الجاية بعد التأجيل، وانتهاء التوكيل، وتذكير الفاتورة المتأخرة برابط دفع. رسايل الجلسات فيها المحكمة والتاريخ والوقت بس، من غير موضوع القضية ولا الخصوم. من غير حساب كل رسالة بتفتح واتساب جاهزة.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الموكلين والقضايا والأرقام في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم جلسات ومواعيد مكتبك؟',
            ] + SystemsCatalogSaas::extras('ar', 'فاتورة أو رصيده كله أو أمانة يطلبها المكتب'),
            'en' => [
                'kicker' => 'Law offices', 'headline' => 'Tomorrow\'s hearings ready, appeal deadlines counted, and the fees of every case clear.',
                'subheadline' => 'Every case has a file with its hearings, decisions, opponents and power of attorney. When a judgment is recorded the appeal and cassation deadlines are worked out, the roll prints per court, and the client sees the case and the account in the portal.',
                'who' => 'Law offices and lawyers with cases in different courts, a team of lawyers, a secretary and an accountant, and clients who are individuals and companies.',
                'problems_title' => 'Why are hearings and deadlines so tiring for an office?',
                'problems' => [
                    ['icon' => 'clock', 'title' => 'An appeal deadline can pass', 'body' => 'Counting 40 or 60 days from the judgment around holidays is done by hand, and one day wrong can lose the case.'],
                    ['icon' => 'users', 'title' => 'One lawyer in two courts at once', 'body' => 'The diary is a notebook, and nobody notices the clash until the hearing day.'],
                    ['icon' => 'calculator', 'title' => 'Mixed-up fees and advances', 'body' => 'Who paid in advance and who owes, and court expenses the office paid are forgotten.'],
                ],
                'showcase_title' => 'The case, the roll and the hearings', 'showcase' => [
                    ['title' => 'The case file', 'body' => 'Hearings with their decisions, the linked cases from first instance to appeal and cassation, and the judgment with its appeal deadline and the law and article.'],
                    ['title' => 'The daily roll', 'body' => 'The day\'s hearings grouped by court with time, circuit, client, opponent and what is required, and an empty decision column, printed on A4.'],
                    ['title' => 'Hearings and clashes', 'body' => 'The hearings diary, a lawyer booked in two courts too close in time, and hearings that passed without a recorded decision.'],
                ],
                'pillars_title' => 'Four things that protect the office',
                'pillars' => [
                    ['icon' => 'clock', 'title' => 'Counted appeal deadlines', 'body' => 'An editable appeal period table where each row names the law and article; the judgment day is not counted, and a last day on a holiday moves to the next working day.'],
                    ['icon' => 'file', 'title' => 'Powers of attorney and memos', 'body' => 'Each power of attorney with its kind, scope and expiry date, and every memo upload is a new version while the old ones are kept.'],
                    ['icon' => 'calculator', 'title' => 'Every kind of fee', 'body' => 'Fixed, per stage, per hearing, a percentage of the award or a monthly retainer, with invoices, case expenses, advances and refunds.'],
                    ['icon' => 'lock', 'title' => 'Confidentiality', 'body' => 'Lawyers see only their own cases, private files are for the author and the owner, and clients see only what is shared with them.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the law office system',
                    'before' => [['b' => 'Hearings', 't' => 'A paper diary and a roll written every night.'], ['b' => 'Deadlines', 't' => 'Appeal and cassation counted by hand.'], ['b' => 'Fees', 't' => 'A notebook for advances and expenses.']],
                    'after' => [['b' => 'Hearings', 't' => 'A ready roll per court and a clash warning.'], ['b' => 'Deadlines', 't' => 'A proposed date from a table with the law and article.'], ['b' => 'Fees', 't' => 'A client statement with invoices and advances.']]],
                'metrics' => [['value' => '6', 'label' => 'Ready roles: owner, partner, lawyer, trainee, secretary and accountant'], ['value' => '5', 'label' => 'Kinds of fee agreement'], ['value' => '146', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs a law office: clients who are individuals and companies, powers of attorney, cases by type (civil, criminal, family, labour, administrative and economic) and degree, hearings whose decisions create the next hearing by themselves, reminders, appeal deadlines and tasks.', 'Inside: documents and memos with versions, legal consultations, a contract drafting log, fee agreements, invoices, receipts and advances, statements and aging, lawyers\' time and a productivity report, and a client portal. One installation serves several offices, and each office sees only its own data. It ships with fictional sample data.'],
                'list' => ['Individual and company clients, and powers of attorney with expiry dates', 'Cases at every degree, linked from first instance to cassation', 'Hearings whose decisions create the next hearing', 'Lawyer clash warnings and a printable daily roll', 'Appeal deadlines from an editable table, and tasks', 'Documents and memos with versions and private files', 'Five kinds of fee agreement, invoices and case expenses', 'Receipts, advances, statements and aging', 'Lawyers\' time and productivity, and a client portal'],
                'faq' => [
                    ['q' => 'Is the appeal period table legally checked?', 'a' => 'No. The table is a starting point written from the text of the laws as we read them, and a practising lawyer must check every row before the office relies on it. Distance periods and special periods are not added by themselves, and the office adds the religious holidays every year.'],
                    ['q' => 'Does it connect to court websites?', 'a' => 'No. Case numbers, rolls and decisions are typed by the office.'],
                    ['q' => 'Does it handle withholding tax or e-invoices for companies?', 'a' => 'No. There is an e-receipt for payments only (professional services), tested against a simulated server and not against the real Egyptian Tax Authority. Ask your accountant.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: a reminder to the client every evening for tomorrow\'s hearing, the next hearing date after an adjournment, an expiring power of attorney, and an overdue invoice reminder with a payment link. Hearing messages carry only the court, the date and the time, never the case subject or the opponents. Without an account every message opens WhatsApp ready to send.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All clients, cases and numbers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to put your office\'s hearings and deadlines in order?',
            ] + SystemsCatalogSaas::extras('en', 'an invoice, the whole balance or an advance the office asked for'),
        ];
    }
}
