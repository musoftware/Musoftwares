<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Nursery and kids club system. Same shape as the systems in SystemsCatalog (hooked in with
 * `...SystemsCatalogExtra::systems(), SystemsCatalogNursery::system()`).
 * The facts come from the system's README, seeder and test suite. Limits are stated on purpose:
 * children's data is sensitive, no legal compliance claim, no parent app, no photo sharing, no automatic WhatsApp,
 * no tax e-receipt connector, demo data is fake.
 */
class SystemsCatalogNursery
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'nursery-system', 'colors' => ['accent' => '#d97706', 'ink' => '#1d2b4f'],
            'name_en' => 'Nursery and Kids Club System', 'name_ar' => 'نظام الحضانة ونادي الأطفال',
            'tagline_en' => 'Safe pickup, daily reports for parents, attendance, and fees with sibling discounts for a nursery.',
            'tagline_ar' => 'استلام آمن وتقرير يومي لولي الأمر وحضور ومصروفات بخصم للإخوة لحضانة أو نادي أطفال.',
            'ar' => [
                'kicker' => 'الحضانات ونوادي الأطفال', 'headline' => 'حضانتك منظمة من أول الحضور لحد استلام الطفل.',
                'subheadline' => 'كل طفل ليه ملف، والاستلام بيتم لشخص معتمد بس، وولي الأمر بيوصله تقرير اليوم على واتساب، والمصروفات والأقساط واضحة.',
                'who' => 'أصحاب الحضانات ونوادي الأطفال والمراكز الصغيرة اللي عايزين يتابعوا الحضور والمصروفات وتقارير اليوم.',
                'problems_title' => 'ليه إدارة الحضانة بتتعب؟',
                'problems' => [
                    ['icon' => 'shield', 'title' => 'استلام من غير تحقق', 'body' => 'مفيش قائمة واضحة بمين يحق له يستلم الطفل، والموظفة بتعتمد على الذاكرة.'],
                    ['icon' => 'message', 'title' => 'أمهات بيسألوا كل يوم', 'body' => 'أكل؟ نام؟ اتبسط؟ الأسئلة دي بتاخد وقت المعلمات.'],
                    ['icon' => 'calculator', 'title' => 'مصروفات متأخرة', 'body' => 'مين دفع ومين متأخر في دفتر، وخصم الإخوة بيتحسب باليد.'],
                ],
                'showcase_title' => 'الحضور والتقرير والمصروفات', 'showcase' => [
                    ['title' => 'حضور وانصراف', 'body' => 'تسجيل الحضور، وعند الانصراف تختار من قائمة المعتمدين. غير كده لازم المالك يكتب الاسم والسبب.'],
                    ['title' => 'تقرير اليوم', 'body' => 'وجبات ونوم ومزاج وحمام وأنشطة وملاحظات. بيتطبع أو يتبعت لولي الأمر على واتساب برابط جاهز.'],
                    ['title' => 'مصروفات وإيصالات', 'body' => 'باقات شهرية أو بالترم، دفعات جزئية، إيصال مرقم، وقائمة المتأخرين برابط تذكير على واتساب.'],
                ],
                'pillars_title' => 'أربع حاجات بتحمي الأطفال والحضانة',
                'pillars' => [
                    ['icon' => 'shield', 'title' => 'استلام معتمد', 'body' => 'أولياء الأمور المسموح لهم وأشخاص إضافيين في قائمة الاستلام. أي شخص غيرهم محتاج المالك.'],
                    ['icon' => 'lock', 'title' => 'خصوصية بالصلاحيات', 'body' => 'الملاحظات الطبية للمالك ومعلمة الطفل بس، وكل فتح ليها بيتسجل. الحساسية بتظهر لكل العاملين للسلامة.'],
                    ['icon' => 'clock', 'title' => 'مستندات وتطعيمات', 'body' => 'تاريخ انتهاء لكل مستند، وقائمة بالمنتهي والقريب من الانتهاء وتذكير لولي الأمر.'],
                    ['icon' => 'users', 'title' => 'خصم الإخوة', 'body' => 'نسبة خصم بيحددها المالك بتتطبق تلقائيًا على كل طفل بعد الأول في نفس العيلة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الحضانة',
                    'before' => [['b' => 'الاستلام', 't' => 'بالذاكرة والتعرف على الوجه.'], ['b' => 'التقارير', 't' => 'كلام شفوي أو رسايل متفرقة.'], ['b' => 'المصروفات', 't' => 'دفتر وحسابات بالقلم.']],
                    'after' => [['b' => 'الاستلام', 't' => 'قائمة معتمدة وسجل لكل استثناء.'], ['b' => 'التقارير', 't' => 'تقرير واحد في اليوم برابط واتساب.'], ['b' => 'المصروفات', 't' => 'فواتير تلقائية وإيصالات ومتأخرات.']]],
                'metrics' => [['value' => '3', 'label' => 'أنواع مستخدمين: المالك والاستقبال والمعلمة'], ['value' => '8', 'label' => 'بنود في تقرير اليوم'], ['value' => '51', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير الحضور والانصراف والمصروفات والتقارير اليومية لحضانة واحدة. كل طفل ليه كود وغرفة وباقة مصروفات، والإخوة بيتجمعوا في عيلة واحدة بنفس أولياء الأمور وقائمة الاستلام.', 'جوه النظام: الأطفال وأولياء الأمور، قائمة الاستلام، الغرف والمعلمات، الحضور، تقارير اليوم، المستندات والتطعيمات، باقات المصروفات والدفعات والإيصالات، وطلبات التسجيل من صفحة الحضانة، وسجل نشاط للمالك. المعلمة بتشوف أطفال غرفتها بس ومبتشوفش أي فلوس. فيه بيانات تجريبية خيالية.'],
                'list' => ['ملف لكل طفل بكود وغرفة وحساسية وملاحظات طبية', 'أولياء أمور وقائمة استلام ومنع الاستلام لغير المعتمدين', 'حضور وانصراف بتسجيل اسم من استلم', 'تقرير يومي قابل للطباعة وإرسال بواتساب (برابط جاهز)', 'مستندات وتطعيمات بتواريخ انتهاء وتذكير', 'باقات مصروفات شهرية وبالترم وفواتير تلقائية', 'خصم الإخوة، دفعات جزئية، إيصالات 80 مم، وقائمة متأخرين', 'طلبات تسجيل من صفحة الحضانة', 'صلاحيات للمالك والاستقبال والمعلمة وسجل نشاط'],
                'faq' => [
                    ['q' => 'بيبعت رسايل واتساب لوحده؟', 'a' => 'لا. النظام بيجهز رابط واتساب بنص التقرير أو التذكير، والموظف هو اللي يدوس إرسال. مفيش إرسال تلقائي.'],
                    ['q' => 'فيه تطبيق لولي الأمر أو مشاركة صور الأطفال؟', 'a' => 'لا. مفيش تطبيق ولا بوابة لولي الأمر ولا مشاركة صور. التقارير بتتبعت على واتساب أو بتتطبع.'],
                    ['q' => 'بيانات الأطفال محمية قانونيًا بالنظام ده؟', 'a' => 'النظام بيحدد مين يشوف إيه وبيسجل فتح الملاحظات الطبية، لكنه مش بيضمن التزامك بأي قانون لحماية البيانات. النسخ الاحتياطي وأمان الخادم والمسؤولية القانونية على الحضانة، واسأل مستشارك القانوني.'],
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => 'لا، مفيش ربط مع الإيصال الإلكتروني في النظام ده. اسأل محاسبك لو نشاطك مطلوب منه.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الأطفال والأسماء وأرقام التليفونات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم حضانتك؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'طرق الدفع المسجلة: نقدي وبطاقة وتحويل (تسجيل فقط، من غير دفع أونلاين)'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. النظام بيسجل طريقة الدفع (نقدي أو بطاقة أو تحويل) لكنه مبيحصلش الدفع بنفسه.'],
            ],
            'en' => [
                'kicker' => 'Nurseries and kids clubs', 'headline' => 'A nursery in order, from arrival to pickup.',
                'subheadline' => 'Every child has a file, children go home only with an approved person, parents get the daily report on WhatsApp, and fees and instalments are clear.',
                'who' => 'Owners of nurseries, kids clubs and small centres who want to follow attendance, fees and daily reports.',
                'problems_title' => 'Why is running a nursery tiring?',
                'problems' => [
                    ['icon' => 'shield', 'title' => 'Pickup with no check', 'body' => 'There is no clear list of who may take a child, so staff rely on memory.'],
                    ['icon' => 'message', 'title' => 'Parents ask every day', 'body' => 'Did they eat? Nap? Have fun? These questions take the teachers\' time.'],
                    ['icon' => 'calculator', 'title' => 'Late fees', 'body' => 'Who paid and who is late sits in a notebook, and the sibling discount is worked out by hand.'],
                ],
                'showcase_title' => 'Attendance, report and fees', 'showcase' => [
                    ['title' => 'Check in and out', 'body' => 'Record arrival, and at pickup choose from the approved list. Anyone else needs the owner, who writes the name and the reason.'],
                    ['title' => 'The daily report', 'body' => 'Meals, nap, mood, toilet, activities and notes. Print it or send the parent a ready WhatsApp link.'],
                    ['title' => 'Fees and receipts', 'body' => 'Monthly or term plans, partial payments, numbered receipts, and an overdue list with a WhatsApp reminder link.'],
                ],
                'pillars_title' => 'Four things that protect the children and the nursery',
                'pillars' => [
                    ['icon' => 'shield', 'title' => 'Approved pickup', 'body' => 'Guardians who may pick up, plus extra people on the pickup list. Anyone else needs the owner.'],
                    ['icon' => 'lock', 'title' => 'Privacy by role', 'body' => 'Medical notes are for the owner and the child\'s teacher only, and every open is logged. Allergies show to all staff for safety.'],
                    ['icon' => 'clock', 'title' => 'Documents and vaccinations', 'body' => 'An expiry date for each document, a list of what is expired or close, and a reminder for the parent.'],
                    ['icon' => 'users', 'title' => 'Sibling discount', 'body' => 'A discount percentage set by the owner applies automatically to every child after the first in the same family.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the nursery system',
                    'before' => [['b' => 'Pickup', 't' => 'By memory and recognising faces.'], ['b' => 'Reports', 't' => 'Spoken words or scattered messages.'], ['b' => 'Fees', 't' => 'A notebook and pen accounts.']],
                    'after' => [['b' => 'Pickup', 't' => 'An approved list and a log of every exception.'], ['b' => 'Reports', 't' => 'One report a day with a WhatsApp link.'], ['b' => 'Fees', 't' => 'Automatic invoices, receipts and overdue list.']]],
                'metrics' => [['value' => '3', 'label' => 'User types: owner, reception and teacher'], ['value' => '8', 'label' => 'Items in the daily report'], ['value' => '51', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs attendance, pickup, fees and daily reports for one nursery. Every child has a code, a room and a fee plan, and siblings are grouped in one family with the same guardians and pickup list.', 'Inside: children and guardians, the pickup list, rooms and teachers, attendance, daily reports, documents and vaccinations, fee plans, payments and receipts, enrolment requests from the nursery page, and an activity log for the owner. A teacher sees only the children of their own room and no money. It ships with fictional sample data.'],
                'list' => ['A file per child with code, room, allergies and medical notes', 'Guardians and a pickup list, and no release to anyone not approved', 'Check in and out, recording who picked the child up', 'A printable daily report with a ready WhatsApp link', 'Documents and vaccinations with expiry dates and reminders', 'Monthly and term fee plans with automatic invoices', 'Sibling discount, partial payments, 80 mm receipts and an overdue list', 'Enrolment requests from the nursery page', 'Roles for owner, reception and teacher, and an activity log'],
                'faq' => [
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'No. The system prepares a WhatsApp link with the text of the report or reminder, and a staff member presses send. Nothing is sent automatically.'],
                    ['q' => 'Is there a parent app or photo sharing?', 'a' => 'No. There is no parent app or portal and no photo sharing. Reports go out on WhatsApp or are printed.'],
                    ['q' => 'Does this system make children\'s data legally compliant?', 'a' => 'It controls who can see what and logs access to medical notes, but it does not guarantee that you comply with any data protection law. Backups, server security and the legal duty stay with the nursery, so ask your legal adviser.'],
                    ['q' => 'Does it support the tax e-receipt?', 'a' => 'No. This system has no e-receipt connector. Ask your accountant whether your business must issue e-receipts.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All children, names and phone numbers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to organize your nursery?',
                'included' => ['Arabic and English with right-to-left support', 'Recorded payment methods: cash, card and transfer (recorded only, no online payment)'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. The system records the payment method (cash, card or transfer) but does not collect the payment itself.'],
            ],
        ];
    }
}
