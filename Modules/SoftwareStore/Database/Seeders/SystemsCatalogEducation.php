<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Education center system (tutoring centers). Same shape as the systems in SystemsCatalog.
 * Facts come from the system's README, seeder and tests. Limits are stated on purpose: messages to parents are prepared
 * but never sent automatically, no ETA connector, no online payment, no parent app, demo data is fake.
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
                'subheadline' => 'الحضور بضغطة، والمصروفات الشهرية بتتعمل لوحدها، وقايمة المتأخرين قدامك مع رسالة واتساب جاهزة لولي الأمر.',
                'who' => 'أصحاب سناتر الدروس والمراكز التعليمية اللي فيها مدرسين ومجموعات وطلاب بيدفعوا كل شهر.',
                'problems_title' => 'ليه الإدارة في السنتر بتتعب؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'كشكول الحضور', 'body' => 'الحضور على ورق بيضيع، ومحدش يعرف الطالب غاب كام مرة.'],
                    ['icon' => 'calculator', 'title' => 'مين دفع الشهر ده؟', 'body' => 'المصروفات في كشكول أو في الدماغ، والمتأخرين بيظهروا في آخر الشهر.'],
                    ['icon' => 'users', 'title' => 'حساب المدرسين', 'body' => 'نسبة كل مدرس بتتحسب بالآلة الحاسبة في آخر الشهر، وفيها خناقات.'],
                ],
                'showcase_title' => 'الحضور والمصروفات والمتأخرات', 'showcase' => [
                    ['title' => 'شاشة الحضور السريعة', 'body' => 'اختار المجموعة والتاريخ، وكل الطلاب حاضرين من الأول. غيّر اللي غايب أو متأخر أو بعذر وخلاص.'],
                    ['title' => 'المصروفات الشهرية', 'body' => 'مصروف لكل طالب في كل مجموعة، ودفع كامل أو على دفعات، وإيصال يتطبع في ثانية.'],
                    ['title' => 'المتأخرات', 'body' => 'قايمة بالمتأخرين وإجمالي كل واحد، وزرار واتساب بيفتح الرسالة جاهزة. أنت اللي بتضغط إرسال.'],
                ],
                'pillars_title' => 'أربع حاجات تريّح إدارة السنتر',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'حضور بأربع حالات', 'body' => 'حاضر ومتأخر وغائب وبعذر، ونسبة حضور لكل طالب.'],
                    ['icon' => 'refresh', 'title' => 'مصروفات بتتعمل لوحدها', 'body' => 'أول الشهر النظام بيعمل مصروف كل طالب مرة واحدة بس، حتى لو الأمر اتكرر. وممكن مصروف مختلف لطالب (إخوة أو منحة).'],
                    ['icon' => 'chart', 'title' => 'نسبة المدرسين', 'body' => 'نسبة كل مدرس من اللي اتحصّل فعلًا في الشهر لمجموعاته، والمتبقي للسنتر.'],
                    ['icon' => 'shield', 'title' => 'صلاحيات', 'body' => 'المالك والاستقبال والمدرس. المدرس يشوف مجموعاته بس ومايشوفش أرقام ولا فلوس.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام السنتر',
                    'before' => [['b' => 'الحضور', 't' => 'كشكول بيضيع وعدّ يدوي.'], ['b' => 'المصروفات', 't' => 'مفيش حد عارف مين متأخر قبل آخر الشهر.'], ['b' => 'المدرسين', 't' => 'حساب النسب بالآلة الحاسبة.']],
                    'after' => [['b' => 'الحضور', 't' => 'شاشة واحدة ونسبة حضور لكل طالب.'], ['b' => 'المصروفات', 't' => 'قايمة المتأخرين كل يوم مع رسالة واتساب جاهزة.'], ['b' => 'المدرسين', 't' => 'تقرير شهري بنسبة كل مدرس.']]],
                'metrics' => [['value' => '72', 'label' => 'طالب تجريبي في النسخة التجريبية'], ['value' => '4', 'label' => 'حالات حضور'], ['value' => '31', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير الطلاب وأولياء الأمور والمجموعات (مادة ومدرس وأيام وميعاد وقاعة ومصروف شهري وعدد أقصى). الطالب ممكن يكون في أكتر من مجموعة، وكل مجموعة ليها مصروفها.', 'جوه النظام: الطلاب وأولياء الأمور، والمجموعات والجدول، والحضور، والمصروفات الشهرية والدفع الجزئي والخصم، والمتأخرات، والامتحانات والدرجات (المتوسط والأعلى والأقل وعدد اللي تحت النص)، وخزينة اليوم حسب طريقة الدفع والموظف، ونسبة المدرسين، وصفحة عامة بالجداول والمصروفات من غير أي بيانات طلاب. فيه بيانات تجريبية خيالية.'],
                'list' => ['طلاب وأولياء أمور بالبحث بالاسم أو الكود أو الهاتف', 'مجموعات بمدرس وأيام وميعاد وقاعة وسعة', 'شاشة حضور سريعة بأربع حالات ونسبة حضور', 'مصروفات شهرية تتعمل مرة واحدة لكل شهر، بدفع جزئي وخصم وإيصال 80 مم', 'مصروف مختلف لطالب (إخوة أو منحة)', 'قايمة المتأخرين مع رسالة واتساب جاهزة لولي الأمر', 'امتحانات ودرجات وإحصائيات', 'خزينة اليوم حسب طريقة الدفع والموظف', 'تقرير نسبة المدرسين الشهري', 'صفحة عامة بالمجموعات والمصروفات من غير بيانات طلاب', 'تشغيل أمر المصروفات الشهرية تلقائيًا بجدولة واحدة على الاستضافة'],
                'faq' => [
                    ['q' => 'بيبعت رسائل واتساب لأولياء الأمور لوحده؟', 'a' => 'لا. النظام بيجهز الرسالة وبيفتح واتساب بنص جاهز، وأنت اللي بتضغط إرسال بنفسك. مفيش إرسال تلقائي ولا ربط بخدمة رسائل.'],
                    ['q' => 'بيدعم الإيصال الإلكتروني لمصلحة الضرائب؟', 'a' => 'لا. النظام مفيهوش ربط بالإيصال الإلكتروني (ETA). اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني.'],
                    ['q' => 'بيانات الطلاب والأطفال محمية قانونيًا؟', 'a' => 'النظام بيحفظ بيانات الطلاب في قاعدة بياناتك أنت، وبيدّي صلاحيات للمالك والاستقبال والمدرس. لكننا مش بنقدم أي ضمان قانوني على التزامك بقوانين حماية البيانات. استشر مختص قانوني.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الأسماء والأرقام والمدفوعات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم السنتر؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'طرق الدفع المسجلة: نقدي وبطاقة وتحويل ومحفظة (تسجيل فقط، من غير دفع أونلاين)'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. النظام بيسجل طريقة الدفع (نقدي أو بطاقة أو تحويل أو محفظة) لكنه مبيحصلش الدفع بنفسه، وولي الأمر بيدفع في السنتر.'],
            ],
            'en' => [
                'kicker' => 'Tutoring and education centers', 'headline' => 'A center that knows who came, who paid and who is late.',
                'subheadline' => 'Attendance in one tap, monthly fees created for you, and a list of late payers with a WhatsApp message ready for the parent.',
                'who' => 'Owners of tutoring centers and learning centers with teachers, groups and students who pay every month.',
                'problems_title' => 'Why is a center hard to run?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'The attendance notebook', 'body' => 'Paper attendance gets lost, and nobody knows how many times a student was absent.'],
                    ['icon' => 'calculator', 'title' => 'Who paid this month?', 'body' => 'Fees live in a notebook or in someone\'s head, and late payers show up at the end of the month.'],
                    ['icon' => 'users', 'title' => 'Paying the teachers', 'body' => 'Each teacher\'s percent is worked out on a calculator at month end, and it causes arguments.'],
                ],
                'showcase_title' => 'Attendance, fees and late payments', 'showcase' => [
                    ['title' => 'Fast attendance screen', 'body' => 'Pick the group and the date and everyone starts as present. Change only the ones who are absent, late or excused.'],
                    ['title' => 'Monthly fees', 'body' => 'One fee per student per group, paid in full or in parts, with a receipt printed in a second.'],
                    ['title' => 'Late payments', 'body' => 'A list of late payers with each total, and a WhatsApp button that opens the message ready. You press send.'],
                ],
                'pillars_title' => 'Four things that make the center easier to run',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Four attendance marks', 'body' => 'Present, late, absent and excused, with an attendance rate for each student.'],
                    ['icon' => 'refresh', 'title' => 'Fees created for you', 'body' => 'At the start of the month the system creates each student\'s fee once, even if the command runs again. A student can have a different fee (siblings or a scholarship).'],
                    ['icon' => 'chart', 'title' => 'Teacher share', 'body' => 'Each teacher gets a percent of the money really collected in the month for his groups. The rest stays with the center.'],
                    ['icon' => 'shield', 'title' => 'Roles', 'body' => 'Owner, reception and teacher. A teacher sees his own groups only, with no phone numbers and no money.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the center system',
                    'before' => [['b' => 'Attendance', 't' => 'A notebook that gets lost and manual counting.'], ['b' => 'Fees', 't' => 'Nobody knows who is late before month end.'], ['b' => 'Teachers', 't' => 'Percents worked out on a calculator.']],
                    'after' => [['b' => 'Attendance', 't' => 'One screen and an attendance rate per student.'], ['b' => 'Fees', 't' => 'A late list every day with a WhatsApp message ready.'], ['b' => 'Teachers', 't' => 'A monthly report with each teacher\'s share.']]],
                'metrics' => [['value' => '72', 'label' => 'Sample students in the demo'], ['value' => '4', 'label' => 'Attendance marks'], ['value' => '31', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system manages students, parents and groups (subject, teacher, days, time, room, monthly fee and a maximum size). A student can be in several groups, and each group has its own fee.', 'Inside: students and parents, groups and timetable, attendance, monthly fees with part payments and discounts, late payments, exams and scores (average, highest, lowest and how many are below half), the daily cash by payment method and staff member, the teacher share, and a public page with timetables and fees that shows no student data. It ships with fictional sample data.'],
                'list' => ['Students and parents, searchable by name, code or phone', 'Groups with a teacher, days, time, room and capacity', 'A fast attendance screen with four marks and an attendance rate', 'Monthly fees created once per month, with part payments, discounts and an 80 mm receipt', 'A different fee for one student (siblings or a scholarship)', 'A list of late payers with a WhatsApp message ready for the parent', 'Exams, scores and statistics', 'Daily cash by payment method and staff member', 'A monthly teacher share report', 'A public page with groups and fees and no student data', 'One scheduled command on your hosting creates the monthly fees automatically'],
                'faq' => [
                    ['q' => 'Does it send WhatsApp messages to parents by itself?', 'a' => 'No. The system prepares the message and opens WhatsApp with the text ready, and you press send yourself. There is no automatic sending and no link to a messaging service.'],
                    ['q' => 'Does it support the tax e-receipt?', 'a' => 'No. The system has no connection to the Egyptian Tax Authority e-receipt (ETA). Ask your accountant whether your business must issue e-receipts.'],
                    ['q' => 'Is student and child data legally protected?', 'a' => 'The system keeps student data in your own database and gives the owner, reception and teachers separate permissions. We do not give any legal guarantee that you comply with data protection law. Ask a legal adviser.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All names, numbers and payments in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to organize your center?',
                'included' => ['Arabic and English with right-to-left support', 'Recorded payment methods: cash, card, transfer and wallet (recorded only, no online payment)'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. The system records the payment method (cash, card, transfer or wallet) but does not collect the payment itself. Parents pay at the center.'],
            ],
        ];
    }
}
