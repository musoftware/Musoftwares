<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * The medical lab and radiology system. Same shape as the systems in SystemsCatalog.
 * The facts come from the system's README, seeder and test suite (LabCenter, 136 tests). Limits are stated on purpose:
 * not a certified medical device, the example reference ranges must be reviewed by the lab's doctor, no analyser (LIS)
 * connection, no DICOM, fake demo data, and the ETA e-receipt connector is built to the documentation but not tested live.
 */
class SystemsCatalogLab
{
    private const ETA_AR = 'أيوه، فيه ربط جاهز بالإيصال الإلكتروني لمصلحة الضرائب (ETA) للمدفوعات النقدية. كتبناه على التوثيق الرسمي للمصلحة واختبرناه على خادم تجريبي بس، ومجربناهوش على خادم المصلحة الحقيقي ومعندناش اعتماد منها. عشان تشغله لازم تسجّل جهاز الكاشير في بوابة المصلحة وتحصل على بياناته وكود الخدمة، وتجرّبه على بيئة التجربة الأول. اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني.';

    private const ETA_EN = 'Yes, a connector for the Egyptian Tax Authority e-receipt (ETA) is included for cash payments. It is written to ETA\'s public documentation and tested only against a simulated server: it has not been tested against the real ETA and it is not certified by ETA. To use it you register your cash register in the ETA portal, get its credentials and your service code, and try it on the ETA test environment first. Ask your accountant whether your business must issue e-receipts.';

    /** @return array<int, array<string, mixed>> */
    public static function systems(): array
    {
        return [self::lab()];
    }

    private static function lab(): array
    {
        return [
            'slug' => 'lab-system', 'colors' => ['accent' => '#0b7a7c', 'ink' => '#06232b'],
            'name_en' => 'Medical Lab and Radiology System', 'name_ar' => 'نظام معمل تحاليل ومركز أشعة',
            'tagline_en' => 'Orders, sample barcodes, results with automatic high and low flags, radiology reports and a private results link for a lab or scan centre.',
            'tagline_ar' => 'طلبات وباركود للعينات ونتائج بعلامات مرتفع ومنخفض تلقائية وتقارير أشعة ورابط نتائج خاص لمعمل أو مركز أشعة.',
            'ar' => [
                'kicker' => 'المعامل ومراكز الأشعة', 'headline' => 'من استقبال العينة لحد تسليم النتيجة، من غير غلطة ولا ورق ضايع.',
                'subheadline' => 'الاستقبال يسجل الطلب والسعر، والعينة تاخد باركود باسم المريض، والفني يكتب النتيجة والنظام يعلّم المرتفع والمنخفض، والمريض يفتح نتيجته برابط خاص على موبايله.',
                'who' => 'المعامل ومراكز الأشعة الصغيرة والمتوسطة اللي عايزة ترتب الاستقبال والعينات والنتائج والخزينة وعمولات الأطباء.',
                'problems_title' => 'ليه شغل المعمل بيتلخبط؟',
                'problems' => [
                    ['icon' => 'box', 'title' => 'عينات بتتلخبط', 'body' => 'عينة من غير اسم واضح ممكن تتحط على مريض غلط.'],
                    ['icon' => 'file', 'title' => 'نتايج بخط اليد', 'body' => 'المدى الطبيعي بيتكتب من الدماغ، والمرتفع والمنخفض بيعدّي من غير ما حد ياخد باله.'],
                    ['icon' => 'calculator', 'title' => 'حساب الدكاترة والخزينة', 'body' => 'عمولة الدكتور المحوّل وخزينة اليوم بتتحسب آخر الشهر على ورق.'],
                ],
                'showcase_title' => 'من الطلب للنتيجة', 'showcase' => [
                    ['title' => 'طلب وسعر وباركود', 'body' => 'اختار التحاليل من القائمة، والسعر بيتاخد من قائمة المعمل، وكل عينة ليها ملصق باركود بنفس الرقم على كل تحاليلها.'],
                    ['title' => 'نتيجة بعلامة تلقائية', 'body' => 'الفني يكتب الرقم والنظام يقارنه بالمدى الطبيعي لسن ونوع المريض، ويعلّم مرتفع أو منخفض أو غير طبيعي.'],
                    ['title' => 'تقرير للطباعة والموبايل', 'body' => 'تقرير A4 بالعربي أو الإنجليزي بترويسة المعمل، ورابط خاص يفتحه المريض برقم تليفونه.'],
                ],
                'pillars_title' => 'أربع حاجات تحمي المعمل والمريض',
                'pillars' => [
                    ['icon' => 'chart', 'title' => 'مدى طبيعي لكل سن ونوع', 'body' => 'تعرّف المدى حسب النوع والفئة العمرية، والنظام يختار الأدق للمريض وينسخه على الطلب فتغيير القائمة بعد كده مبيغيّرش نتيجة قديمة.'],
                    ['icon' => 'lock', 'title' => 'أشعة بخصوصية', 'body' => 'تقارير الأشعة وصورها (صور وPDF) للمالك والدكتور بس. الاستقبال والفني ميفتحوهاش، والصور بتتخزن بأسماء عشوائية وكل فتح بيتسجل.'],
                    ['icon' => 'shield', 'title' => 'نتيجة برابط خاص', 'body' => 'المريض يفتح نتيجته برابطه ورقم تليفونه، وبس لما كل التحاليل تخلص والفاتورة تتسدد. وصور الأشعة مبتظهرش أونلاين.'],
                    ['icon' => 'users', 'title' => 'أربع صلاحيات وسجل مراجعة', 'body' => 'مالك واستقبال وفني ودكتور، وسجل لكل مرة اتفتحت فيها نتيجة أو اتطبع تقرير أو اتسلمت نتيجة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام المعمل',
                    'before' => [['b' => 'العينة', 't' => 'اسم بخط اليد على الأنبوبة.'], ['b' => 'النتيجة', 't' => 'مقارنة بالمدى من الذاكرة.'], ['b' => 'الدكاترة', 't' => 'عمولات بتتحسب آخر الشهر.']],
                    'after' => [['b' => 'العينة', 't' => 'ملصق باركود باسم المريض.'], ['b' => 'النتيجة', 't' => 'علامة تلقائية حسب سن ونوع المريض.'], ['b' => 'الدكاترة', 't' => 'تقرير عمولات لأي فترة.']]],
                'metrics' => [['value' => '38', 'label' => 'تحليل وأشعة في القائمة المبدئية بأسعار مثال'], ['value' => '4', 'label' => 'صلاحيات للموظفين'], ['value' => '136', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير المعمل من الاستقبال للتسليم. كل طلب بيحفظ نسخة من اسم التحليل وسعره ومداه الطبيعي وقت الطلب، فتغيير السعر أو المدى بعد كده مبيأثرش على طلب قديم. حالة الطلب بتتحدد من حالة تحاليله: تم الاستلام، قيد التحليل، جاهز، تم التسليم. التسليم بيحتاج إن كل التحاليل تكون جاهزة، والاستقبال مينفعش يسلّم قبل ما الفاتورة تتسدد، لكن المالك ممكن يقرر غير كده.', 'جوه النظام: قائمة التحاليل والأسعار والمدى الطبيعي، وطلبات بخصم ودفعات جزئية وإيصالات 80 مم وA4، وقائمة عمل للفني بالبحث بباركود العينة، وتقارير أشعة بصور وملفات PDF خاصة، وعمولات الأطباء المحوّلين، وخزينة اليوم، وسجل المراجعة، وموقع عام بالأسعار. فيه بيانات تجريبية خيالية، وكل صور الأشعة فيها مكتوب عليها "تجريبية".'],
                'list' => ['قائمة تحاليل وأشعة بالأسعار والمدى الطبيعي حسب النوع والسن', 'طلب بباركود لكل عينة وخصم ودفعات جزئية وإيصالات', 'قائمة عمل للفني وإدخال نتائج بعلامات تلقائية', 'تقرير نتائج للطباعة A4 بالعربي والإنجليزي', 'رابط نتيجة خاص للمريض برقم تليفونه', 'تقارير أشعة وصور وPDF خاصة بسجل فتح', 'عمولات الأطباء المحوّلين وخزينة اليوم', 'مالك واستقبال وفني ودكتور وسجل مراجعة', 'إرسال الدفعات النقدية للإيصال الإلكتروني (ETA)، ويحتاج بيانات مسجّلة عندك'],
                'faq' => [
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => self::ETA_AR],
                    ['q' => 'النظام جهاز طبي معتمد؟ والمدى الطبيعي صح؟', 'a' => 'لا. النظام برنامج إدارة، مش جهاز طبي معتمد. المدى الطبيعي في القائمة المبدئية أمثلة بس، وكل معمل ليه جهازه وطريقته، فلازم دكتور المعمل يراجع كل مدى وكل سعر قبل أول مريض حقيقي.'],
                    ['q' => 'بيتوصل بأجهزة التحليل أو بصور DICOM؟', 'a' => 'لا. مفيش ربط بأجهزة التحليل (LIS) ولا DICOM. النتايج بتتكتب باليد، والصور المدعومة JPG وPNG وWebP وملفات PDF بس.'],
                    ['q' => 'بيانات المرضى محمية قانونيًا بالنظام ده؟', 'a' => 'النظام بيحمي الأشعة وبيسجل الدخول، لكنه مش بيضمن التزامك بقانون حماية البيانات الشخصية. المسؤولية القانونية على المعمل، واسأل مستشارك القانوني.'],
                ],
                'cta_title' => 'جاهز تنظم معملك؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'دفع نقدي داخل النظام بإيصالات مرقمة'],
            ],
            'en' => [
                'kicker' => 'Labs and scan centres', 'headline' => 'From taking the sample to handing over the result, with no mix-ups and no lost paper.',
                'subheadline' => 'Reception registers the order and the price, each sample gets a barcode with the patient\'s name, the technician types the result and the system flags high and low values, and the patient opens the result on a private phone link.',
                'who' => 'Small and medium labs and scan centres that want to organize reception, samples, results, the cash desk and doctors\' commissions.',
                'problems_title' => 'Why does a lab get messy?',
                'problems' => [
                    ['icon' => 'box', 'title' => 'Mixed-up samples', 'body' => 'A sample without a clear name can end up on the wrong patient.'],
                    ['icon' => 'file', 'title' => 'Handwritten results', 'body' => 'The normal range is written from memory, and a high or low value slips through unnoticed.'],
                    ['icon' => 'calculator', 'title' => 'Doctors and the cash desk', 'body' => 'A referring doctor\'s commission and the day\'s cash are worked out on paper at the end of the month.'],
                ],
                'showcase_title' => 'From the order to the result', 'showcase' => [
                    ['title' => 'Order, price and barcode', 'body' => 'Pick the tests from the list, the price comes from the lab\'s list, and each sample gets one barcode label shared by all its tests.'],
                    ['title' => 'Automatic result flags', 'body' => 'The technician types the number and the system compares it with the normal range for the patient\'s age and sex, and flags high, low or abnormal.'],
                    ['title' => 'A report for print and phone', 'body' => 'An A4 report in Arabic or English with the lab\'s header, and a private link the patient opens with their phone number.'],
                ],
                'pillars_title' => 'Four things that protect the lab and the patient',
                'pillars' => [
                    ['icon' => 'chart', 'title' => 'A range for each age and sex', 'body' => 'Define ranges by sex and age band, the system picks the most specific one for the patient and copies it onto the order, so a later change never rewrites an old result.'],
                    ['icon' => 'lock', 'title' => 'Private radiology', 'body' => 'Radiology reports and images (pictures and PDF) are for the owner and the doctor only. Reception and technicians cannot open them, files are stored under random names and every open is logged.'],
                    ['icon' => 'shield', 'title' => 'A private results link', 'body' => 'The patient opens their result with their link and phone number, and only when every test is done and the bill is paid. Radiology images are never shown online.'],
                    ['icon' => 'users', 'title' => 'Four roles and an audit log', 'body' => 'Owner, reception, technician and doctor, and a log of every time a result is opened, a report is printed or a result is handed over.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the lab system',
                    'before' => [['b' => 'Sample', 't' => 'A handwritten name on the tube.'], ['b' => 'Result', 't' => 'Compared with the range from memory.'], ['b' => 'Doctors', 't' => 'Commissions worked out at month end.']],
                    'after' => [['b' => 'Sample', 't' => 'A barcode label with the patient\'s name.'], ['b' => 'Result', 't' => 'An automatic flag for the patient\'s age and sex.'], ['b' => 'Doctors', 't' => 'A commission report for any period.']]],
                'metrics' => [['value' => '38', 'label' => 'Tests and scans in the starter list, with example prices'], ['value' => '4', 'label' => 'Staff roles'], ['value' => '136', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs the lab from reception to hand-over. Each order keeps a copy of the test name, price and normal range as they were when it was taken, so a later price or range change never touches an old order. An order\'s status follows its tests: received, in progress, ready, handed over. Handing over needs every test to be done, and reception cannot hand over before the bill is paid, but the owner can decide otherwise.', 'Inside: a test and price list with reference ranges, orders with a discount, partial payments and 80 mm and A4 receipts, a technician work list searchable by sample barcode, radiology reports with private images and PDF files, referring doctors\' commissions, the daily cash, an audit log and a public website with the prices. It ships with fictional sample data, and every X-ray in the demo is marked as a demo image.'],
                'list' => ['A test and scan list with prices and reference ranges by sex and age', 'Orders with a barcode for each sample, a discount, partial payments and receipts', 'A technician work list and result entry with automatic flags', 'An A4 results report in Arabic and English', 'A private results link for the patient, opened with their phone number', 'Radiology reports, images and PDF files, private and logged', 'Referring doctors\' commissions and the daily cash', 'Owner, reception, technician and doctor roles with an audit log', 'Cash payments sent to the tax e-receipt (ETA) (needs your own registration)'],
                'faq' => [
                    ['q' => 'Does it support the tax e-receipt?', 'a' => self::ETA_EN],
                    ['q' => 'Is it a certified medical device? Are the reference ranges right?', 'a' => 'No. It is management software, not a certified medical device. The reference ranges in the starter list are examples only. Every lab has its own analyser and method, so the lab doctor must review every range and every price before the first real patient.'],
                    ['q' => 'Does it connect to analysers or take DICOM images?', 'a' => 'No. There is no analyser (LIS) connection and no DICOM. Results are typed by hand, and the only supported files are JPG, PNG, WebP and PDF.'],
                    ['q' => 'Does this system make patient data legally compliant?', 'a' => 'The system protects radiology files and logs access, but it does not guarantee that you comply with personal data protection law. The legal duty stays with the lab, so ask your legal adviser.'],
                ],
                'cta_title' => 'Ready to organize your lab?',
                'included' => ['Arabic and English with right-to-left support', 'Cash payments inside the system with numbered receipts'],
            ],
        ];
    }
}
