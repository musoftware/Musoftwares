<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * CRM with full Egyptian double-entry accounting and the ETA e-invoice and e-receipt. Same shape as the systems in
 * SystemsCatalog (listed in SystemsCatalogSaas). The facts come from the system's README, seeders and test suite.
 * Limits are stated on purpose: the ETA connectors were never run against the real ETA, no payroll, simple stock,
 * the simplified-regime tier table is not checked against the regulations, demo data is fake.
 */
class SystemsCatalogCrmAccounting
{
    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'crm-accounting-system', 'colors' => ['accent' => '#4f46e5', 'ink' => '#14123a'], 'screens' => SystemsCatalogSaas::SCREENS,
            'name_en' => 'CRM and Accounting System with ETA e-Invoice', 'name_ar' => 'نظام إدارة العملاء والحسابات مع الفاتورة الإلكترونية',
            'tagline_en' => 'Leads, deals and quotations on top of full double-entry accounting with the Egyptian chart of accounts, VAT and the ETA e-invoice and e-receipt.',
            'tagline_ar' => 'ليدز وصفقات وعروض أسعار فوق حسابات كاملة بالقيد المزدوج وشجرة الحسابات المصرية والقيمة المضافة والفاتورة والإيصال الإلكتروني.',
            'ar' => [
                'kicker' => 'الشركات الخدمية والتجارية', 'headline' => 'المبيعات والحسابات في نظام واحد، وكل مستند بيعمل قيده.',
                'subheadline' => 'من أول الليد للصفقة وعرض السعر والفاتورة والتحصيل، كل خطوة بتنزل في دفتر الأستاذ، وميزان المراجعة والميزانية وإقرار القيمة المضافة جاهزين.',
                'who' => 'أي شركة خدمات أو تجارة أو تصنيع خفيف عندها سيلز ومحاسب، وعايزة إدارة العملاء والحسابات والضرايب في مكان واحد.',
                'problems_title' => 'ليه المبيعات والحسابات مش بيكلموا بعض؟',
                'problems' => [
                    ['icon' => 'users', 'title' => 'السيلز في شيت والمحاسب في برنامج تاني', 'body' => 'الصفقة اتقفلت بس الفاتورة اتأخرت، ومحدش عارف العميل دفع ولا لأ.'],
                    ['icon' => 'calculator', 'title' => 'قيود بإيد المحاسب', 'body' => 'كل فاتورة وتحصيل وشيك بيتكتب قيده تاني، والغلط بيبان آخر السنة.'],
                    ['icon' => 'file', 'title' => 'الفاتورة الإلكترونية صداع', 'body' => 'تجهيز الفاتورة للمصلحة وتوقيعها ومتابعة حالتها شغل يدوي كل يوم.'],
                ],
                'showcase_title' => 'من الليد للقيد', 'showcase' => [
                    ['title' => 'أداء المبيعات', 'body' => 'الليدز بمصدرها وحملتها، ووقت أول رد، ونسبة التحويل والنجاح لكل سيلز، وتوقع الصفقات بالشهر.'],
                    ['title' => 'الفاتورة وقيدها', 'body' => 'سطور بخصم وقيمة مضافة لكل سطر وخصم وإضافة، والقيد بيتعمل لوحده ويبان تحت الفاتورة، ومعاها رابط دفع وزرار واتساب.'],
                    ['title' => 'ميزان المراجعة', 'body' => 'ميزان بالمستويات وقائمة دخل وميزانية وتدفقات نقدية وأستاذ عام وأعمار ديون، وكل تقرير بيقول هو متوازن ولا لأ.'],
                ],
                'pillars_title' => 'أربع حاجات بتريح المحاسب',
                'pillars' => [
                    ['icon' => 'lock', 'title' => 'قيد مبيتعدلش', 'body' => 'القيد المرحّل مبيتمسحش ولا بيتعدل، الغلط بيتصلح بقيد عكسي، والشهر المقفول بيرفض أي ترحيل.'],
                    ['icon' => 'file', 'title' => 'الفاتورة والإيصال الإلكتروني', 'body' => 'فاتورة إلكترونية للشركات وإيصال إلكتروني للأفراد، بالتوقيع والإرسال ومتابعة الحالة والإلغاء، وكود QR على المطبوع.'],
                    ['icon' => 'refresh', 'title' => 'شيكات ومخزون', 'body' => 'سجل للشيكات الواردة والصادرة، والشيك المرتد بيرجّع الفاتورة مفتوحة، ومخزون بمتوسط التكلفة في أكتر من مخزن.'],
                    ['icon' => 'chart', 'title' => 'ضرايب جاهزة', 'body' => 'إقرار القيمة المضافة شهري أو ربع سنوي، وقائمة الخصم والإضافة (نموذج 41)، وتقرير ضريبة النظام المبسط للمشروعات الصغيرة.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام العملاء والحسابات',
                    'before' => [['b' => 'الصفقات', 't' => 'شيت السيلز بعيد عن الفواتير.'], ['b' => 'القيود', 't' => 'كل مستند بيتقيد بالإيد.'], ['b' => 'الضرايب', 't' => 'الإقرار بيتجمع من الدفاتر.']],
                    'after' => [['b' => 'الصفقات', 't' => 'الصفقة بتبقى عرض سعر وبعدين فاتورة.'], ['b' => 'القيود', 't' => 'كل مستند بيرحّل قيده لوحده.'], ['b' => 'الضرايب', 't' => 'الإقرار وقائمة الخصم جاهزين من القيود.']]],
                'metrics' => [['value' => '5', 'label' => 'مستندات: عرض سعر وفاتورة وإشعار دائن وفاتورة شراء وإشعار مدين'], ['value' => '6', 'label' => 'أدوار جاهزة: المالك والمحاسب ومدير المبيعات والسيلز والمشتريات وأمين المخزن'], ['value' => '177', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيجمع إدارة العملاء (ليدز وصفقات وأنشطة وحملات) مع حسابات كاملة بالقيد المزدوج لأي شركة. دفتر الأستاذ هو المصدر الوحيد للأرقام: كل فاتورة وتحصيل وشيك وأصل ومصروف بيرحّل قيده، وكل تقرير بيتحسب من القيود.', 'جوه النظام: جهات الاتصال والشركات بحقول انت بتضيفها، والليدز والصفقات على لوحة بالسحب، وعروض الأسعار والفواتير والإشعارات وفواتير الشراء، والتحصيل والسداد بالشيكات، والمخزون، وشجرة حسابات على الدليل المصري، والسنين والفترات المالية، والأرصدة الافتتاحية، والخزن والبنوك والتسوية البنكية، والأصول والإهلاك، وتقارير بطباعة A4 وتصدير CSV. نسخة واحدة بتخدم أكتر من شركة، وكل شركة بتشوف بياناتها بس. فيه بيانات تجريبية خيالية لسنة كاملة.'],
                'list' => ['جهات اتصال وشركات وليدز وحملات بتكلفة الليد والعميل', 'صفقات على لوحة بالسحب وأسباب الخسارة وتوقع المبيعات', 'استيراد وتصدير CSV بيظهر عربي في إكسل', 'عروض أسعار وفواتير وإشعارات دائنة ومدينة وفواتير شراء', 'تحصيل وسداد بالنقدي والتحويل والشيك وسجل الشيكات', 'شجرة حسابات مصرية وقيود يومية وقيد عكسي وقفل الشهور والسنة', 'خزن وبنوك وتسوية بنكية وأصول ثابتة بالإهلاك', 'إقرار القيمة المضافة وقائمة الخصم والإضافة وتقرير النظام المبسط', 'فاتورة إلكترونية للشركات وإيصال إلكتروني للأفراد'],
                'faq' => [
                    ['q' => 'الفاتورة الإلكترونية متجربة على مصلحة الضرائب؟', 'a' => 'لا. الربط مكتوب على صفحات التوثيق الرسمية ومتجرب على خادم تجريبي بس، ومتبعتش منه أي مستند لخادم المصلحة الحقيقي (لا التجربة ولا الفعلي)، ومعندناش اعتماد منها. التوقيع محتاج شهادتك أو التوكن وخدمة توقيع عندك. جرّبه على بيئة التجربة الأول واسأل محاسبك.'],
                    ['q' => 'فيه مرتبات؟', 'a' => 'لا. المرتبات بتتسجل مصروف أو قيد يومية.'],
                    ['q' => 'المخزون فيه تشغيلات أو أرقام مسلسلة؟', 'a' => 'لا. المخزون بسيط: متوسط تكلفة واحد لكل صنف في كل المخازن، من غير تشغيلات ولا أرقام مسلسلة ولا أوامر تصنيع.'],
                    ['q' => 'تقرير النظام المبسط معتمد؟', 'a' => 'لا. جدول النسب بيتعدل لكل شركة لأن المصادر مختلفة ومتراجعش على اللائحة التنفيذية للقانون. التقرير تقدير مش إقرار، فراجعه مع محاسبك.'],
                    ['q' => 'بيبعت واتساب لوحده؟', 'a' => 'لو ربطت حساب واتساب Business: الرسالة من رقم جديد بتبقى ليد، والفاتورة وتذكير المتأخر بيتبعتوا من صفحة الفاتورة، والتذكير بيتبعت لوحده كل يوم للفواتير المتأخرة (مرة في الأسبوع بالكتير لكل فاتورة). من غير حساب كل رسالة بتفتح واتساب جاهزة.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل الشركات والأسماء والأرقام في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تخلّي كل مستند يعمل قيده؟',
            ] + SystemsCatalogSaas::extras('ar', 'المتبقي على فاتورة مبيعات بالجنيه'),
            'en' => [
                'kicker' => 'Service and trading companies', 'headline' => 'Sales and accounts in one system, and every document posts its entry.',
                'subheadline' => 'From the first lead to the deal, the quotation, the invoice and the receipt, every step lands in the general ledger, and the trial balance, balance sheet and VAT return are ready.',
                'who' => 'Any service, trading or light manufacturing company with sales people and an accountant that wants customers, accounts and taxes in one place.',
                'problems_title' => 'Why do sales and accounts not talk to each other?',
                'problems' => [
                    ['icon' => 'users', 'title' => 'Sales in a sheet, the accountant in another program', 'body' => 'The deal is closed but the invoice is late, and nobody knows whether the customer paid.'],
                    ['icon' => 'calculator', 'title' => 'Entries typed by hand', 'body' => 'Every invoice, receipt and cheque is entered a second time, and mistakes show at year end.'],
                    ['icon' => 'file', 'title' => 'The e-invoice is a headache', 'body' => 'Preparing invoices for the tax authority, signing them and following their status is manual work every day.'],
                ],
                'showcase_title' => 'From lead to ledger', 'showcase' => [
                    ['title' => 'Sales performance', 'body' => 'Leads by source and campaign, first response time, conversion and win rate per sales person, and a pipeline forecast by month.'],
                    ['title' => 'The invoice and its entry', 'body' => 'Lines with discount and VAT per line and withholding tax; the entry posts by itself and shows under the invoice, with a payment link and a WhatsApp button.'],
                    ['title' => 'Trial balance', 'body' => 'Trial balance by level, income statement, balance sheet, cash flow, general ledger and aging, and every report states whether it balances.'],
                ],
                'pillars_title' => 'Four things that make the accountant\'s day easier',
                'pillars' => [
                    ['icon' => 'lock', 'title' => 'Entries that never change', 'body' => 'Posted entries cannot be edited or deleted; mistakes are corrected with a reversing entry, and a locked month refuses postings.'],
                    ['icon' => 'file', 'title' => 'E-invoice and e-receipt', 'body' => 'The e-invoice for companies and the e-receipt for consumers, signed, sent, followed and cancelled, with a QR code on the printout.'],
                    ['icon' => 'refresh', 'title' => 'Cheques and stock', 'body' => 'A register of received and issued cheques; a bounced cheque opens the invoice again; stock at moving average cost in several warehouses.'],
                    ['icon' => 'chart', 'title' => 'Taxes ready', 'body' => 'The VAT return monthly or quarterly, the withholding tax list (Form 41), and a turnover tax report for the simplified small business regime.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the CRM and accounting system',
                    'before' => [['b' => 'Deals', 't' => 'The sales sheet is far from the invoices.'], ['b' => 'Entries', 't' => 'Every document is entered by hand.'], ['b' => 'Taxes', 't' => 'The return is collected from the books.']],
                    'after' => [['b' => 'Deals', 't' => 'A deal becomes a quotation, then an invoice.'], ['b' => 'Entries', 't' => 'Every document posts its own entry.'], ['b' => 'Taxes', 't' => 'The return and the withholding list come from the entries.']]],
                'metrics' => [['value' => '5', 'label' => 'Documents: quotation, invoice, credit note, supplier bill and debit note'], ['value' => '6', 'label' => 'Ready roles: owner, accountant, sales manager, sales rep, purchasing and storekeeper'], ['value' => '177', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['The system joins customer management (leads, deals, activities and campaigns) with full double-entry accounting for any company. The general ledger is the only source of the numbers: every invoice, receipt, cheque, asset and expense posts its entry, and every report is computed from the entries.', 'Inside: contacts and companies with your own custom fields, leads and deals on a drag-and-drop board, quotations, invoices, notes and supplier bills, receipts and payments with cheques, stock, a chart of accounts on the Egyptian model, fiscal years and periods, opening balances, cash boxes, banks and bank reconciliation, fixed assets with depreciation, and reports that print on A4 and export to CSV. One installation serves several companies, and each company sees only its own data. It ships with a year of fictional sample data.'],
                'list' => ['Contacts, companies, leads and campaigns with cost per lead and per customer', 'Deals on a drag-and-drop board, lost reasons and a sales forecast', 'CSV import and export that shows Arabic correctly in Excel', 'Quotations, invoices, credit and debit notes and supplier bills', 'Receipts and payments by cash, transfer and cheque, with a cheque register', 'Egyptian chart of accounts, journal entries, reversals and locked months and years', 'Cash boxes, banks, bank reconciliation and fixed assets with depreciation', 'VAT return, withholding tax list and the simplified regime report', 'E-invoice for companies and e-receipt for consumers'],
                'faq' => [
                    ['q' => 'Is the e-invoice tested against the tax authority?', 'a' => 'No. The connector follows the official documentation pages and is tested only against a simulated server. No document was sent to the real ETA servers (test or live), and it is not certified by ETA. Signing needs your certificate or USB token and a signing service on your side. Try it on the ETA test environment first and ask your accountant.'],
                    ['q' => 'Is there payroll?', 'a' => 'No. Salaries are recorded as an expense or a journal entry.'],
                    ['q' => 'Does stock have batches or serial numbers?', 'a' => 'No. Stock is simple: one moving average cost per product over all warehouses, with no batches, serial numbers or production orders.'],
                    ['q' => 'Is the simplified regime report official?', 'a' => 'No. The tier table is editable per company because sources differ and it was not checked against the law\'s executive regulations. The report is an estimate, not a tax return, so review it with your accountant.'],
                    ['q' => 'Does it send WhatsApp messages by itself?', 'a' => 'With a WhatsApp Business account connected: a message from a new number becomes a lead, the invoice and the overdue reminder are sent from the invoice page, and overdue reminders go out by themselves every day (at most once a week per invoice). Without an account every message opens WhatsApp ready to send.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All companies, names and numbers in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to let every document post its own entry?',
            ] + SystemsCatalogSaas::extras('en', 'what is left on a sales invoice in Egyptian pounds'),
        ];
    }
}
