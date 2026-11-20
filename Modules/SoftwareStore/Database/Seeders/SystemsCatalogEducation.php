<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Education center system (tutoring centers). Same shape as the systems in SystemsCatalog.
 * Facts come from the system's README, seeder and tests. Limits are stated on purpose: WhatsApp messages go out by
 * themselves only with the owner's own WhatsApp Business account, online payment only through links (tested against
 * simulated answers only), no ETA connector, no parent portal or app, demo data is fake.
 */
class SystemsCatalogEducation
{
    /** @return array<int, array<string, mixed>> */
    public static function systems(): array
    {
        return [self::education()];
    }

    private static function education(): array
    {
        return [
            'slug' => 'education-center-system', 'colors' => ['accent' => '#d9930a', 'ink' => '#10243e'],
            'name_en' => 'Education Center System', 'name_ar' => 'نظام إدارة السناتر والمراكز التعليمية',
            'tagline_en' => 'Students, groups, attendance, monthly fees and parent reminders for a tutoring center.',
            'tagline_ar' => 'طلاب ومجموعات وحضور ومصروفات شهرية وتذكير لأولياء الأمور لسنتر دروس.',
            'ar' => [
                'kicker' => 'السناتر والمراكز التعليمية', 'headline' => 'السنتر بتاعك منظم: مين حضر، ومين دفع، ومين متأخر.',
                'subheadline' => 'الحضور بضغطة، والمصروفات الشهرية بتتعمل لوحدها، وقايمة المتأخرين قدامك، وتذكير واتساب لولي الأمر ورابط يدفع بيه أونلاين.',
                'who' => 'أصحاب سناتر الدروس والمراكز التعليمية اللي فيها مدرسين ومجموعات وطلاب بيدفعوا كل شهر.',
                'problems_title' => 'ليه الإدارة في السنتر بتتعب؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'كشكول الحضور', 'body' => 'الحضور على ورق بيضيع، ومحدش يعرف الطالب غاب كام مرة.'],
                    ['icon' => 'calculator', 'title' => 'مين دفع الشهر ده؟', 'body' => 'المصروفات في كشكول أو في الدماغ، والمتأخرين بيظهروا في آخر الشهر.'],
                    ['icon' => 'users', 'title' => 'حساب المدرسين', 'body' => 'نسبة كل مدرس بتتحسب بالآلة الحاسبة في آخر الشهر، وفيها خناقات.'],
                ],
                'showcase_title' => 'الحضور والمصروفات والمتأخرات', 'showcase' => [
                    ['title' => 'شاشة الحضور السريعة', 'body' => 'اختار المجموعة والتاريخ، وكل الطلاب حاضرين من الأول. غيّر اللي غايب أو متأخر أو بعذر وخلاص.'],
                    ['title' => 'المصروفات الشهرية', 'body' => 'مصروف لكل طالب في كل مجموعة، ودفع كامل أو على دفعات، وإيصال يتطبع في ثانية، ورابط دفع أونلاين تبعته لولي الأمر.'],
                    ['title' => 'المتأخرات', 'body' => 'قايمة بالمتأخرين وإجمالي كل واحد. بحساب واتساب Business التذكير بيتبعت لوحده مرة في الأسبوع بالكتير، ومن غيره الزرار بيفتح الرسالة جاهزة وانت بتضغط إرسال.'],
                ],
                'pillars_title' => 'أربع حاجات تريّح إدارة السنتر',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'حضور بأربع حالات', 'body' => 'حاضر ومتأخر وغائب وبعذر، ونسبة حضور لكل طالب، وإشعار غياب لولي الأمر على واتساب.'],
                    ['icon' => 'refresh', 'title' => 'مصروفات بتتعمل لوحدها', 'body' => 'أول الشهر النظام بيعمل مصروف كل طالب مرة واحدة بس، حتى لو الأمر اتكرر. وممكن مصروف مختلف لطالب (إخوة أو منحة).'],
                    ['icon' => 'chart', 'title' => 'نسبة المدرسين', 'body' => 'نسبة كل مدرس من اللي اتحصّل فعلًا في الشهر لمجموعاته، والمتبقي للسنتر.'],
                    ['icon' => 'shield', 'title' => 'صلاحيات', 'body' => 'المالك والاستقبال والمدرس. المدرس يشوف مجموعاته بس ومايشوفش أرقام ولا فلوس.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام السنتر',
                    'before' => [['b' => 'الحضور', 't' => 'كشكول بيضيع وعدّ يدوي.'], ['b' => 'المصروفات', 't' => 'مفيش حد عارف مين متأخر قبل آخر الشهر.'], ['b' => 'المدرسين', 't' => 'حساب النسب بالآلة الحاسبة.']],
                    'after' => [['b' => 'الحضور', 't' => 'شاشة واحدة ونسبة حضور لكل طالب.'], ['b' => 'المصروفات', 't' => 'قايمة المتأخرين كل يوم وتذكير واتساب ورابط دفع.'], ['b' => 'المدرسين', 't' => 'تقرير شهري بنسبة كل مدرس.']]],
                'metrics' => [['value' => '72', 'label' => 'طالب تجريبي في النسخة التجريبية'], ['value' => '4', 'label' => 'حالات حضور'], ['value' => '91', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['النظام بيدير الطلاب وأولياء الأمور والمجموعات (مادة ومدرس وأيام وميعاد وقاعة ومصروف شهري وعدد أقصى). الطالب ممكن يكون في أكتر من مجموعة، وكل مجموعة ليها مصروفها.', 'جوه النظام: الطلاب وأولياء الأمور، والمجموعات والجدول، والحضور، والمصروفات الشهرية والدفع الجزئي والخصم وروابط الدفع أونلاين، والمتأخرات وتذكيرها على واتساب، وإشعارات الغياب، ومحادثة ولي الأمر على واتساب في صفحة الطالب، والامتحانات والدرجات (المتوسط والأعلى والأقل وعدد اللي تحت النص)، وخزينة اليوم حسب طريقة الدفع والموظف، ونسبة المدرسين، وصفحة عامة بالجداول والمصروفات من غير أي بيانات طلاب. فيه بيانات تجريبية خيالية.'],
                'list' => ['طلاب وأولياء أمور بالبحث بالاسم أو الكود أو الهاتف', 'مجموعات بمدرس وأيام وميعاد وقاعة وسعة', 'شاشة حضور سريعة بأربع حالات ونسبة حضور', 'مصروفات شهرية تتعمل مرة واحدة لكل شهر، بدفع جزئي وخصم وإيصال 80 مم', 'مصروف مختلف لطالب (إخوة أو منحة)', 'رابط دفع أونلاين لمصروف الشهر (Paymob وفوري وإنستاباي)', 'قايمة المتأخرين وتذكير واتساب لولي الأمر، لوحده أو برسالة جاهزة', 'إشعار غياب على واتساب لولي الأمر يوم الحصة', 'امتحانات ودرجات وإحصائيات', 'خزينة اليوم حسب طريقة الدفع والموظف', 'تقرير نسبة المدرسين الشهري', 'صفحة عامة بالمجموعات والمصروفات من غير بيانات طلاب', 'مهمة مجدولة واحدة على الاستضافة بتعمل المصروفات الشهرية وتبعت التذكيرات وتتابع الدفع أونلاين'],
                'faq' => [
                    ['q' => 'بيبعت رسائل واتساب لأولياء الأمور لوحده؟', 'a' => 'لو ربطت حساب واتساب Business بتاعك: أيوه. تذكير المتأخرات بيتبعت كل يوم الساعة 11 لولي الأمر مرة في الأسبوع بالكتير، وإشعار الغياب بيتبعت لما تتسجل غياب النهارده، وردود ولي الأمر بتظهر في صفحة الطالب، ولو بعت "توقف" الرسايل بتقف. الردود مش آلية، ومفيش رسايل SMS. من غير حساب كل زرار بيفتح واتساب برسالة جاهزة وانت بتضغط إرسال. الربط متجرب على ردود تجريبية بس، ومتجربش على خوادم Meta.'],
                    ['q' => 'بيدعم الإيصال الإلكتروني لمصلحة الضرائب؟', 'a' => 'لا. النظام مفيهوش ربط بالإيصال الإلكتروني (ETA). اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني.'],
                    ['q' => 'بيانات الطلاب والأطفال محمية قانونيًا؟', 'a' => 'النظام بيحفظ بيانات الطلاب في قاعدة بياناتك أنت، وبيدّي صلاحيات للمالك والاستقبال والمدرس. لكننا مش بنقدم أي ضمان قانوني على التزامك بقوانين حماية البيانات. استشر مختص قانوني.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الأسماء والأرقام والمدفوعات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم السنتر؟',
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'للمصروفات الشهرية وتذكير المتأخرين على واتساب ومتابعة الدفع أونلاين'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'مصروف الشهر كله أو جزء منه'),
            ],
            'en' => [
                'kicker' => 'Tutoring and education centers', 'headline' => 'A center that knows who came, who paid and who is late.',
                'subheadline' => 'Attendance in one tap, monthly fees created for you, and a list of late payers, with a WhatsApp reminder to the parent and a link to pay online.',
                'who' => 'Owners of tutoring centers and learning centers with teachers, groups and students who pay every month.',
                'problems_title' => 'Why is a center hard to run?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'The attendance notebook', 'body' => 'Paper attendance gets lost, and nobody knows how many times a student was absent.'],
                    ['icon' => 'calculator', 'title' => 'Who paid this month?', 'body' => 'Fees live in a notebook or in someone\'s head, and late payers show up at the end of the month.'],
                    ['icon' => 'users', 'title' => 'Paying the teachers', 'body' => 'Each teacher\'s percent is worked out on a calculator at month end, and it causes arguments.'],
                ],
                'showcase_title' => 'Attendance, fees and late payments', 'showcase' => [
                    ['title' => 'Fast attendance screen', 'body' => 'Pick the group and the date and everyone starts as present. Change only the ones who are absent, late or excused.'],
                    ['title' => 'Monthly fees', 'body' => 'One fee per student per group, paid in full or in parts, with a receipt printed in a second, and an online payment link you send to the parent.'],
                    ['title' => 'Late payments', 'body' => 'A list of late payers with each total. With a WhatsApp Business account the reminder goes out by itself, at most once a week; without one the button opens the message ready and you press send.'],
                ],
                'pillars_title' => 'Four things that make the center easier to run',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Four attendance marks', 'body' => 'Present, late, absent and excused, with an attendance rate for each student and an absence notice to the parent on WhatsApp.'],
                    ['icon' => 'refresh', 'title' => 'Fees created for you', 'body' => 'At the start of the month the system creates each student\'s fee once, even if the command runs again. A student can have a different fee (siblings or a scholarship).'],
                    ['icon' => 'chart', 'title' => 'Teacher share', 'body' => 'Each teacher gets a percent of the money really collected in the month for his groups. The rest stays with the center.'],
                    ['icon' => 'shield', 'title' => 'Roles', 'body' => 'Owner, reception and teacher. A teacher sees his own groups only, with no phone numbers and no money.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the center system',
                    'before' => [['b' => 'Attendance', 't' => 'A notebook that gets lost and manual counting.'], ['b' => 'Fees', 't' => 'Nobody knows who is late before month end.'], ['b' => 'Teachers', 't' => 'Percents worked out on a calculator.']],
                    'after' => [['b' => 'Attendance', 't' => 'One screen and an attendance rate per student.'], ['b' => 'Fees', 't' => 'A late list every day, a WhatsApp reminder and a payment link.'], ['b' => 'Teachers', 't' => 'A monthly report with each teacher\'s share.']]],
                'metrics' => [['value' => '72', 'label' => 'Sample students in the demo'], ['value' => '4', 'label' => 'Attendance marks'], ['value' => '91', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['The system manages students, parents and groups (subject, teacher, days, time, room, monthly fee and a maximum size). A student can be in several groups, and each group has its own fee.', 'Inside: students and parents, groups and timetable, attendance, monthly fees with part payments, discounts and online payment links, late payments and their WhatsApp reminders, absence notices, the WhatsApp conversation with the parent on the student page, exams and scores (average, highest, lowest and how many are below half), the daily cash by payment method and staff member, the teacher share, and a public page with timetables and fees that shows no student data. It ships with fictional sample data.'],
                'list' => ['Students and parents, searchable by name, code or phone', 'Groups with a teacher, days, time, room and capacity', 'A fast attendance screen with four marks and an attendance rate', 'Monthly fees created once per month, with part payments, discounts and an 80 mm receipt', 'A different fee for one student (siblings or a scholarship)', 'An online payment link for a monthly fee (Paymob, Fawry and InstaPay)', 'A list of late payers and a WhatsApp reminder to the parent, by itself or as a ready message', 'A WhatsApp absence notice to the parent on the day of the class', 'Exams, scores and statistics', 'Daily cash by payment method and staff member', 'A monthly teacher share report', 'A public page with groups and fees and no student data', 'One scheduled job on your hosting creates the monthly fees, sends the reminders and checks online payments'],
                'faq' => [
                    ['q' => 'Does it send WhatsApp messages to parents by itself?', 'a' => 'With your own WhatsApp Business account connected: yes. Late-payment reminders go out every day at 11:00, at most once a week per student, absence notices go out when today\'s roll call marks a student absent, the parent\'s replies show on the student page, and a "STOP" reply stops the messages. Replies are not automated, and there is no SMS. Without an account every button opens WhatsApp with the message ready and you press send. The connection is tested against simulated answers only, not against Meta\'s servers.'],
                    ['q' => 'Does it support the tax e-receipt?', 'a' => 'No. The system has no connection to the Egyptian Tax Authority e-receipt (ETA). Ask your accountant whether your business must issue e-receipts.'],
                    ['q' => 'Is student and child data legally protected?', 'a' => 'The system keeps student data in your own database and gives the owner, reception and teachers separate permissions. We do not give any legal guarantee that you comply with data protection law. Ask a legal adviser.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All names, numbers and payments in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to organize your center?',
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for the monthly fees, WhatsApp late-payment reminders and online payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'all or part of a monthly fee'),
            ],
        ];
    }
}
