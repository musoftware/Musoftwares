<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Private and international school system. Same shape as the systems in SystemsCatalog (listed in SystemsCatalogSaas).
 * The facts come from the system's README, seeders and test suite. Limits are stated on purpose: no salary tax or insurance
 * calculation, no timetable builder, no SMS or e-mail, the ETA e-receipt and the VAT per fee type are untested starting points,
 * no legal compliance claim for student data, demo data is fake.
 */
class SystemsCatalogSchool
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'private-school-system', 'colors' => ['accent' => '#4338ca', 'ink' => '#1e1b4b'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'Private and International School System', 'name_ar' => 'نظام إدارة المدارس الخاصة والدولية',
            'tagline_en' => 'Admissions, fees with instalments and sibling discounts, attendance, report cards, buses and a parent portal for a private school.',
            'tagline_ar' => 'قبول ومصروفات بأقساط وخصم إخوة وحضور وشهادات وأتوبيسات وبوابة لولي الأمر لمدرسة خاصة أو دولية.',
            'ar' => [
                'kicker' => 'المدارس الخاصة واللغات والدولية', 'headline' => 'المصروفات والحضور والشهادات في مكان واحد، وولي الأمر شايف ولاده.',
                'subheadline' => 'الطالب بيتقبل ويتوزع على فصل، وفاتورة المصروفات بتتعمل بخصم الإخوة والأقساط، والإيصال بيسدد أقدم قسط، والمدرس بيدخل الحضور والدرجات لفصوله بس، وولي الأمر بيشوف كل ده من البوابة.',
                'who' => 'المدارس الخاصة والتجريبية والدولية (نظام مصري أو دولي بتقديرات من A* لحد U)، بمرحلة واحدة أو أكتر، وعندها أتوبيسات ومدرسين وموظفين.',
                'problems_title' => 'ليه إدارة المدرسة بتتعب الحسابات والمدرسين؟',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'مصروفات وأقساط في دفاتر', 'body' => 'مين دفع ومين اتأخر، وخصم الأخ التاني والتالت ومنحة الطالب بيتحسبوا بالإيد.'],
                    ['icon' => 'clock', 'title' => 'حضور ودرجات على ورق', 'body' => 'كشوف الغياب ودرجات الامتحانات بتتجمع في الآخر، والشهادة بتتكتب بالإيد.'],
                    ['icon' => 'message', 'title' => 'أولياء أمور بيسألوا كل يوم', 'body' => 'عليا كام؟ ابني حضر؟ الأتوبيس اتأخر؟ أسئلة بتاخد وقت السكرتارية.'],
                ],
                'showcase_title' => 'المصروفات والشهادة وبوابة ولي الأمر', 'showcase' => [
                    ['title' => 'المتأخرات', 'body' => 'الأسر اللي عليها أقساط فات ميعادها بالمبلغ ومن إمتى، وتذكير على واتساب فيه رابط دفع.'],
                    ['title' => 'الشهادة', 'body' => 'شهادة لكل ترم أو للسنة بالعربي أو الإنجليزي، بالتقدير والنسبة والحضور، جاهزة للطباعة.'],
                    ['title' => 'بوابة ولي الأمر', 'body' => 'ولي الأمر بيشوف حضور ولاده ودرجاتهم وشهاداتهم والأتوبيس والمصروفات المستحقة والإيصالات، ويدفع أونلاين.'],
                ],
                'pillars_title' => 'أربع حاجات بتنظم المدرسة',
                'pillars' => [
                    ['icon' => 'users', 'title' => 'قبول بقائمة انتظار', 'body' => 'استفسار ثم اختبار ثم مقابلة ثم قبول، ولو الصف مليان الطالب بيدخل قائمة انتظار بالترتيب.'],
                    ['icon' => 'calculator', 'title' => 'خصومات مظبوطة', 'body' => 'خصم الطفل التاني والتالت أو خصم ابن الموظف، وبعدين منحة بنسبة. والفاتورة بتتنسخ وقت إصدارها فمبتتغيرش بعدين.'],
                    ['icon' => 'lock', 'title' => 'كل واحد شايف شغله', 'body' => 'المدرس بياخد الحضور ويدخل الدرجات لفصوله ومواده بس، وولي الأمر بيشوف ولاده بس.'],
                    ['icon' => 'file', 'title' => 'إيصال إلكتروني للضرائب', 'body' => 'كل إيصال ممكن يتبعت لمصلحة الضرائب كإيصال تعليم، والإيصال الملغي بيتبعت إيصال مرتجع.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام المدرسة',
                    'before' => [['b' => 'المصروفات', 't' => 'دفتر وحسابات بالقلم.'], ['b' => 'الشهادات', 't' => 'درجات بتتجمع وتتكتب بالإيد.'], ['b' => 'أولياء الأمور', 't' => 'مكالمات وأسئلة كل يوم.']],
                    'after' => [['b' => 'المصروفات', 't' => 'فواتير بأقساط وإيصالات وقائمة متأخرين.'], ['b' => 'الشهادات', 't' => 'شهادة بالعربي أو الإنجليزي جاهزة للطباعة.'], ['b' => 'أولياء الأمور', 't' => 'بوابة فيها الحضور والدرجات والمصروفات.']]],
                'metrics' => [['value' => '6', 'label' => 'أدوار جاهزة: المالك والمدير والمحاسب والتسجيل والمدرس ومسؤول النقل'], ['value' => '2', 'label' => 'نظامين للتقدير: مصري ودولي'], ['value' => '165', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير مدرسة خاصة من القبول للشهادة: سنين دراسية وترمات، ومراحل بالنظام المصري (ترمين) أو الدولي (تلات ترمات)، وصفوف وفصول بسعة، ومواد لكل صف. كل طالب ليه كود وولي أمر أو أكتر، وواحد منهم هو اللي بيدفع، والإخوة بيتعرفوا من ولي الأمر.', 'جوه النظام: القبول، والمصروفات بقوائم لكل صف وخطط تقسيط وخصومات ومنح، والإيصالات (نقدي وتحويل وشيك وأونلاين) بطباعة A4 أو 80 مم، وكشف حساب وأعمار ديون ومتأخرين، والجدول الدراسي من غير تعارض للمدرس، وحضور الطلاب والموظفين، والامتحانات والشهادات، والأتوبيسات وخطوطها، والإعلانات، والموظفين والعقود والمرتبات، وبوابة ولي الأمر. نسخة واحدة بتخدم أكتر من مدرسة، وكل مدرسة بتشوف بياناتها بس. فيه بيانات تجريبية خيالية.'],
                'list' => ['قبول من الاستفسار للتسجيل بقائمة انتظار', 'طلاب وأولياء أمور وإخوة بنفس ولي الأمر الدافع', 'مصروفات لكل صف وأتوبيس حسب المحطة وخطط تقسيط', 'خصم الإخوة وابن الموظف والمنح', 'إيصالات A4 و80 مم وكشف حساب وأعمار ديون ومتأخرين', 'جدول دراسي وحضور طلاب وموظفين', 'امتحانات بأوزان وشهادات بالعربي والإنجليزي', 'أتوبيسات وخطوط ومحطات', 'موظفين وعقود ومرتبات وبوابة لولي الأمر'],
                'faq' => [
                    ['q' => 'بيحسب ضريبة المرتبات والتأمينات؟', 'a' => 'لا. المرتب هو الأساسي والبدلات ناقص الغياب والخصومات، والضريبة والتأمينات بتتكتب خصومات بإيدك.'],
                    ['q' => 'بيعمل الجدول الدراسي لوحده؟', 'a' => 'لا. الجدول بيتكتب بإيدك، والنظام بيمنع إن المدرس يبقى في فصلين في نفس الحصة.'],
                    ['q' => 'الإيصال الإلكتروني متجرب على مصلحة الضرائب؟', 'a' => 'لا. مكتوب على التوثيق الرسمي ومتجرب على خادم تجريبي بس. ونسبة الضريبة على كل بند (المصاريف والكتب والزي والأتوبيس) مجرد بداية، اسأل محاسبك أنهي بند معفي وأنهي بند عليه ضريبة.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: إشعار الغياب بيتبعت لوحده بعد الحضور، وتذكير المتأخرات فيه رابط دفع، والإعلانات وتنبيه تأخير الأتوبيس بيتبعتوا لأولياء الأمور. من غير حساب، تذكير المتأخرات وإشعار الغياب بيفتحوا واتساب برسالة جاهزة، والإعلانات محتاجة الحساب. مفيش رسايل نصية ولا إيميل.'],
                    ['q' => 'بيانات الطلاب محمية قانونيًا بالنظام ده؟', 'a' => 'النظام بيحدد مين يشوف إيه، لكنه مش بيضمن التزامك بأي قانون لحماية البيانات. انت اللي بتحدد مين ياخد حساب، واسأل مستشارك القانوني.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الطلاب والأسماء وأرقام التليفونات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم مصروفات مدرستك وشهاداتها؟',
            ] + SystemsCatalogSaas::extras('ar', 'حساب الأسرة كله أو فاتورة طالب واحد'),
            'en' => [
                'kicker' => 'Private, language and international schools', 'headline' => 'Fees, attendance and report cards in one place, and parents see their children.',
                'subheadline' => 'A student is admitted and placed in a section, the fee invoice is made with sibling discounts and instalments, a receipt pays the oldest instalment, teachers enter attendance and marks for their own sections only, and parents see it all in the portal.',
                'who' => 'Private, experimental and international schools (the national system or the international A* to U grades), with one stage or more, buses, teachers and staff.',
                'problems_title' => 'Why does running a school tire the accountant and the teachers?',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'Fees and instalments in notebooks', 'body' => 'Who paid and who is late, and the second and third child discount and scholarships worked out by hand.'],
                    ['icon' => 'clock', 'title' => 'Attendance and marks on paper', 'body' => 'Absence sheets and exam marks are collected at the end, and report cards are written by hand.'],
                    ['icon' => 'message', 'title' => 'Parents ask every day', 'body' => 'How much do I owe? Did my son attend? Is the bus late? Questions that take the office\'s time.'],
                ],
                'showcase_title' => 'Fees, report cards and the parent portal', 'showcase' => [
                    ['title' => 'Late payments', 'body' => 'Families with instalments past their due date, with the amount and since when, and a WhatsApp reminder with a payment link.'],
                    ['title' => 'The report card', 'body' => 'A report card per term or for the year, in Arabic or English, with ratings, percentages and attendance, ready to print.'],
                    ['title' => 'The parent portal', 'body' => 'Parents see their children\'s attendance, marks, report cards, bus, fees due and receipts, and pay online.'],
                ],
                'pillars_title' => 'Four things that keep the school in order',
                'pillars' => [
                    ['icon' => 'users', 'title' => 'Admissions with a waiting list', 'body' => 'Inquiry, entrance test, interview, then acceptance. When the grade is full the child joins a waiting list in order.'],
                    ['icon' => 'calculator', 'title' => 'Correct discounts', 'body' => 'Second and third child or staff child discount, then a scholarship percentage. The invoice is copied when issued, so it never changes later.'],
                    ['icon' => 'lock', 'title' => 'Everyone sees their own work', 'body' => 'Teachers take attendance and enter marks only for their own sections and subjects, and parents see only their own children.'],
                    ['icon' => 'file', 'title' => 'Tax e-receipt', 'body' => 'Every receipt can be sent to the Egyptian Tax Authority as an education receipt, and a cancelled receipt goes out as a return receipt.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the school system',
                    'before' => [['b' => 'Fees', 't' => 'A notebook and pen accounts.'], ['b' => 'Report cards', 't' => 'Marks collected and written by hand.'], ['b' => 'Parents', 't' => 'Calls and questions every day.']],
                    'after' => [['b' => 'Fees', 't' => 'Invoices with instalments, receipts and a late list.'], ['b' => 'Report cards', 't' => 'A report card in Arabic or English, ready to print.'], ['b' => 'Parents', 't' => 'A portal with attendance, marks and fees.']]],
                'metrics' => [['value' => '6', 'label' => 'Ready roles: owner, principal, accountant, registrar, teacher and transport officer'], ['value' => '2', 'label' => 'Grading systems: national and international'], ['value' => '165', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs a private school from admission to report card: academic years and terms, stages on the national system (two terms) or the international system (three terms), grades and sections with a capacity, and subjects per grade. Every student has a code and one or more guardians, one of whom pays, and siblings are found through the paying guardian.', 'Inside: admissions, fee lists per grade with instalment plans, discounts and scholarships, receipts (cash, transfer, cheque and online) printed on A4 or an 80 mm roll, statements, aging and a late list, a timetable that never puts a teacher in two sections at once, student and staff attendance, exams and report cards, buses and routes, announcements, staff, contracts and payroll, and the parent portal. One installation serves several schools, and each school sees only its own data. It ships with fictional sample data.'],
                'list' => ['Admissions from inquiry to enrolment with a waiting list', 'Students, guardians and siblings under the same paying guardian', 'Fees per grade, bus fee by stop, and instalment plans', 'Sibling, staff child and scholarship discounts', 'A4 and 80 mm receipts, statements, aging and a late list', 'Timetable and student and staff attendance', 'Weighted exams and report cards in Arabic and English', 'Buses, routes and stops', 'Staff, contracts, payroll and a parent portal'],
                'faq' => [
                    ['q' => 'Does it calculate salary tax and social insurance?', 'a' => 'No. Pay is basic plus allowances minus absent days and deductions; tax and insurance are typed in as deductions.'],
                    ['q' => 'Does it build the timetable by itself?', 'a' => 'No. The timetable is entered by hand, and the system stops a teacher from being in two sections in the same period.'],
                    ['q' => 'Is the e-receipt tested against the tax authority?', 'a' => 'No. It follows the official documentation and is tested only against a simulated server. The VAT rate per fee type (tuition, books, uniform, bus) is only a starting point: ask your accountant which fees are exempt and which are taxed.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: the absence notice goes out by itself after the roll call, the late fee reminder carries a payment link, and announcements and bus delay notices go to parents. Without an account the late fee reminder and the absence notice open WhatsApp ready to send, and announcements need the account. There are no SMS or e-mail messages.'],
                    ['q' => 'Does this system make student data legally compliant?', 'a' => 'It controls who can see what, but it does not guarantee that you comply with any data protection law. You decide who gets a login, so ask your legal adviser.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All students, names and phone numbers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to put your school\'s fees and report cards in order?',
            ] + SystemsCatalogSaas::extras('en', 'the whole family account or one child\'s fee invoice'),
        ];
    }
}
