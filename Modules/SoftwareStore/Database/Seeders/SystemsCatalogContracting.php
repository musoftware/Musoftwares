<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Contracting and construction projects system. Same shape as the systems in SystemsCatalog (listed in SystemsCatalogSaas).
 * The facts come from the system's README, seeders and test suite. Limits are stated on purpose: no ETA e-invoice (that
 * belongs to an accounting system), tax rates per project are not tax advice, no general ledger or payroll, no critical
 * path or price escalation, government clients get no payment link, demo data is fake.
 */
class SystemsCatalogContracting
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'contracting-system', 'colors' => ['accent' => '#c2410c', 'ink' => '#2a1206'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'Contracting and Construction Projects System', 'name_ar' => 'نظام شركات المقاولات وإدارة المشروعات',
            'tagline_en' => 'Bills of quantities, site measurements, progress invoices, subcontractors, site stores and budget against actual cost for a contracting company.',
            'tagline_ar' => 'مقايسات وحصر كميات ومستخلصات ومقاولين باطن ومخازن مواقع وموازنة قدام التكلفة الفعلية لشركة مقاولات.',
            'ar' => [
                'kicker' => 'شركات المقاولات', 'headline' => 'المستخلص بيطلع من الحصر، والتكلفة بتتقارن بالموازنة.',
                'subheadline' => 'مهندس الموقع بيسجل الحصر والتقرير اليومي من الموبايل، ومهندس الكميات بيعتمد، والمستخلص بيتحسب تراكمي بالتأمين واسترداد الدفعة المقدمة والضرايب، وكل بند ليه تكلفة فعلية قدام موازنته.',
                'who' => 'شركات المقاولات العامة والتشطيبات والإنشاءات اللي عندها أكتر من مشروع ومقاولين باطن ومخازن مواقع، وعملاء شركات أو جهات حكومية أو أفراد.',
                'problems_title' => 'ليه مشروع المقاولات بيخسر من غير ما تعرف؟',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'مستخلص على إكسل', 'body' => 'الكميات السابقة والحالية والتراكمي والتأمين والدفعة المقدمة بتتحسب بالإيد، وأي غلط بيروح للعميل.'],
                    ['icon' => 'box', 'title' => 'خامات بتتصرف من غير حساب', 'body' => 'الأسمنت والحديد بيخرجوا من المخزن ومحدش عارف راحوا على أنهي بند.'],
                    ['icon' => 'chart', 'title' => 'الربح بيبان في الآخر', 'body' => 'العمالة والمعدات والباطن والعهد بتتجمع بعد ما المشروع يخلص.'],
                ],
                'showcase_title' => 'المشروع والمستخلص وشغل الموقع', 'showcase' => [
                    ['title' => 'نظرة على المشروع', 'body' => 'العقد الأصلي والمعدل بأوامر التغيير، والمنفذ والمصروف، والأنشطة المتأخرة، وآخر التقارير اليومية.'],
                    ['title' => 'المستخلص', 'body' => 'لكل بند السابق والحالي والتراكمي بالكمية والقيمة، وبعده التأمين واسترداد الدفعة المقدمة والخصومات والضرايب والصافي المستحق، بطباعة A4.'],
                    ['title' => 'الموقع من الموبايل', 'body' => 'مهندس الموقع بيكتب التقرير اليومي بالطقس والعمالة والصور، والحصر، ومصروفات العهدة.'],
                ],
                'pillars_title' => 'أربع حاجات بتحمي هامش الربح',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'حصر ثم اعتماد', 'body' => 'الحصر اللي أكتر من الكمية المعدلة بيترفض لحد ما أمر تغيير يتعتمد، والمستخلص المعتمد بيتقفل.'],
                    ['icon' => 'users', 'title' => 'مقاولين الباطن', 'body' => 'عقود باطن ببنودها ومستخلصات بنفس القواعد، وبوابة للمقاول يقدم مستخلصه ومهندس الكميات يراجعه.'],
                    ['icon' => 'box', 'title' => 'مشتريات ومخازن مواقع', 'body' => 'طلب خامات ثم أمر شراء ثم استلام ثم صرف على بند بمتوسط التكلفة، والمخزن مبينزلش تحت الصفر.'],
                    ['icon' => 'calculator', 'title' => 'الموازنة قدام الفعلي', 'body' => 'الخامات والعمالة والمعدات والباطن والعهد قدام موازنة كل بند، والربح لحد النهارده والمتوقع، وتوقع للتدفق النقدي لست شهور.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام المقاولات',
                    'before' => [['b' => 'المستخلص', 't' => 'شيت إكسل بيتنسخ كل شهر.'], ['b' => 'الموقع', 't' => 'تقارير على واتساب وورق.'], ['b' => 'التكلفة', 't' => 'بتتعرف بعد ما المشروع يخلص.']],
                    'after' => [['b' => 'المستخلص', 't' => 'مستخلص تراكمي من الحصر المعتمد.'], ['b' => 'الموقع', 't' => 'تقرير يومي وحصر وعهدة من الموبايل.'], ['b' => 'التكلفة', 't' => 'تكلفة كل بند قدام موازنته كل يوم.']]],
                'metrics' => [['value' => '6', 'label' => 'أدوار جاهزة: المالك ومدير المشروع ومهندس الموقع ومهندس الكميات والمحاسب وأمين المخزن'], ['value' => '4', 'label' => 'مشروعات كاملة في البيانات التجريبية'], ['value' => '123', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيدير مشروعات شركة مقاولات من المقايسة للتسليم. كل مشروع ليه عميل وعقد وشروط دفع: نسبة التأمين، والدفعة المقدمة ونسبة استردادها، والقيمة المضافة والخصم من المنبع، ومدة السداد. والمقايسة بتتكتب بند بند أو تتلزق من إكسل.', 'جوه النظام: أوامر التغيير، والحصر، ومستخلصات العميل ومقاولين الباطن، والمشتريات ومخازن المواقع، والمعدات بالساعة، والتقارير اليومية بالصور، والعهد النقدية وتسويتها، والموازنة قدام الفعلي، وتوقع التدفق النقدي، وكشوف حساب وأعمار ديون للعملاء والموردين والباطن، والمستندات بتواريخ انتهاء، وجدول زمني بسيط. نسخة واحدة بتخدم أكتر من شركة، وكل شركة بتشوف بياناتها بس. فيه بيانات تجريبية خيالية لأربع مشروعات.'],
                'list' => ['مقايسة ببنود وأقسام وسعر وتكلفة موازنة، ولزق من إكسل', 'أوامر تغيير بالزيادة والنقص وبنود جديدة ومدد إضافية', 'حصر كميات من الموقع واعتماد مهندس الكميات', 'مستخلصات تراكمية بالتأمين والدفعة المقدمة والضرايب وطباعة A4', 'مقاولين باطن بعقود ومستخلصات وبوابة خاصة بيهم', 'طلبات خامات وأوامر شراء واستلام وصرف ومخازن مواقع', 'معدات ملك وإيجار بالساعة', 'تقارير يومية بالصور وعهد نقدية وتسويتها', 'موازنة قدام الفعلي وتوقع تدفق نقدي وكشوف حساب وأعمار ديون'],
                'faq' => [
                    ['q' => 'بيبعت المستخلص فاتورة إلكترونية للضرائب؟', 'a' => 'لا. النظام مبيبعتش حاجة لمصلحة الضرائب. مستخلص الشركة أو الجهة الحكومية فاتورة إلكترونية بين شركات، بتتعمل من نظام الحسابات (زي نظام إدارة العملاء والحسابات عندنا). المستخلص المطبوع مكتوب عليه كده.'],
                    ['q' => 'فيه دفتر أستاذ ومرتبات؟', 'a' => 'لا. مفيش حسابات عامة ولا مرتبات ولا تسوية بنكية. تكلفة العمالة هي عدد العمال في اليومية من التقارير اليومية.'],
                    ['q' => 'فيه فروق أسعار ومسار حرج؟', 'a' => 'لا. مفيش معادلات فروق أسعار ولا حساب مسار حرج ولا عملات أجنبية. الجدول الزمني مخطط وفعلي لكل نشاط.'],
                    ['q' => 'نسب الضرايب مظبوطة؟', 'a' => 'النسب بتتحدد لكل مشروع على حسب العقد، ومش نصيحة ضريبية. راجعها مع محاسبك.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: ملخص التقرير اليومي بيروح لمدير المشروع، وإشعار تقديم المستخلص واعتماده بيروح للعميل، وتنبيه المستندات اللي هتخلص. ملخص العميل مفيهوش مشاكل ولا تكاليف. من غير حساب كل رسالة بتفتح واتساب جاهزة.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل المشروعات والعملاء والأسماء في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تعرف كل مشروع بيكسب كام وهو شغال؟',
            ] + SystemsCatalogSaas::extras('ar', 'مستخلص معتمد أو رصيده على المشروع (العميل الحكومي مبيظهرلوش رابط)'),
            'en' => [
                'kicker' => 'Contracting companies', 'headline' => 'The progress invoice comes from the measurements, and the cost is compared with the budget.',
                'subheadline' => 'The site engineer records measurements and the daily report on the phone, the quantity surveyor approves, the progress invoice is cumulative with retention, advance recovery and taxes, and every item has its actual cost next to its budget.',
                'who' => 'General contracting, finishing and building companies with several projects, subcontractors and site stores, and clients that are companies, government bodies or individuals.',
                'problems_title' => 'Why does a contracting project lose money without you knowing?',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'Progress invoices in Excel', 'body' => 'Previous, current and cumulative quantities, retention and advance recovery are worked out by hand, and any mistake goes to the client.'],
                    ['icon' => 'box', 'title' => 'Materials issued without a trace', 'body' => 'Cement and steel leave the store and nobody knows which item they went to.'],
                    ['icon' => 'chart', 'title' => 'Profit shows at the end', 'body' => 'Labour, equipment, subcontractors and petty cash are added up after the project is finished.'],
                ],
                'showcase_title' => 'The project, the progress invoice and the site', 'showcase' => [
                    ['title' => 'Project overview', 'body' => 'The original and revised contract with variation orders, work done and certified, late tasks and the latest daily reports.'],
                    ['title' => 'The progress invoice', 'body' => 'For each item the previous, current and cumulative quantity and value, then retention, advance recovery, deductions, taxes and the net due, printed on A4.'],
                    ['title' => 'The site on the phone', 'body' => 'The site engineer writes the daily report with weather, workers and photos, the measurements and the petty cash expenses.'],
                ],
                'pillars_title' => 'Four things that protect the margin',
                'pillars' => [
                    ['icon' => 'check', 'title' => 'Measure, then approve', 'body' => 'A measurement above the revised quantity is refused until a variation order is approved, and an approved progress invoice is frozen.'],
                    ['icon' => 'users', 'title' => 'Subcontractors', 'body' => 'Subcontracts with their own items and certificates on the same rules, and a portal where the subcontractor submits and the quantity surveyor checks.'],
                    ['icon' => 'box', 'title' => 'Purchasing and site stores', 'body' => 'Material request, purchase order, delivery, then issue to an item at moving average cost. Stock never goes below zero.'],
                    ['icon' => 'calculator', 'title' => 'Budget against actual', 'body' => 'Materials, labour, equipment, subcontractors and petty cash against each item\'s budget, profit to date and forecast, and a six-month cash-flow forecast.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the contracting system',
                    'before' => [['b' => 'Progress invoice', 't' => 'An Excel sheet copied every month.'], ['b' => 'Site', 't' => 'Reports on WhatsApp and paper.'], ['b' => 'Cost', 't' => 'Known after the project ends.']],
                    'after' => [['b' => 'Progress invoice', 't' => 'A cumulative invoice from approved measurements.'], ['b' => 'Site', 't' => 'Daily report, measurements and petty cash on the phone.'], ['b' => 'Cost', 't' => 'Each item\'s cost next to its budget every day.']]],
                'metrics' => [['value' => '6', 'label' => 'Ready roles: owner, project manager, site engineer, quantity surveyor, accountant and storekeeper'], ['value' => '4', 'label' => 'Complete projects in the demo data'], ['value' => '123', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system runs the projects of a contracting company from the bill of quantities to handover. Each project has a client, a contract and payment terms: retention, advance payment and its recovery rate, VAT and withholding tax, and days to pay. The bill of quantities is typed item by item or pasted from Excel.', 'Inside: variation orders, site measurements, client and subcontractor progress invoices, purchasing and site stores, equipment by the hour, daily reports with photos, petty cash and its settlement, budget against actual, a cash-flow forecast, statements and aging for clients, suppliers and subcontractors, documents with expiry dates and a simple schedule. One installation serves several companies, and each company sees only its own data. It ships with fictional sample data for four projects.'],
                'list' => ['Bill of quantities with items, sections, price and budget cost, pasted from Excel', 'Variation orders that add, omit or create items and grant extra days', 'Site measurements approved by the quantity surveyor', 'Cumulative progress invoices with retention, advance and taxes, printed on A4', 'Subcontractors with subcontracts, certificates and their own portal', 'Material requests, purchase orders, deliveries, issues and site stores', 'Owned and rented equipment by the hour', 'Daily reports with photos, and petty cash with settlement', 'Budget against actual, cash-flow forecast, statements and aging'],
                'faq' => [
                    ['q' => 'Does it send progress invoices to the tax authority as e-invoices?', 'a' => 'No. The system sends nothing to the Egyptian Tax Authority. A progress invoice to a company or a government body is a business-to-business e-invoice, issued from the accounting system (such as our CRM and accounting system). The printed invoice says so.'],
                    ['q' => 'Is there a general ledger and payroll?', 'a' => 'No. There is no general ledger, payroll or bank reconciliation. Own labour cost is workers times the daily rate from the daily reports.'],
                    ['q' => 'Is there price escalation or a critical path?', 'a' => 'No. There are no price escalation formulas, no critical path calculation and no foreign currencies. The schedule is planned against actual dates per task.'],
                    ['q' => 'Are the tax rates correct?', 'a' => 'Rates are set per project according to the contract and are not tax advice. Check them with your accountant.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: the daily report summary goes to the project manager, the progress invoice submitted and approved notices go to the client, and expiring documents are flagged. The client summary carries no problems or costs. Without an account every message opens WhatsApp ready to send.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All projects, clients and names in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to know what every project earns while it runs?',
            ] + SystemsCatalogSaas::extras('en', 'an approved progress invoice or the project balance (government clients are never offered a link)'),
        ];
    }
}
