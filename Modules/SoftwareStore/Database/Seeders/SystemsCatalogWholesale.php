<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * The wholesale and distribution system. Same shape as the systems in SystemsCatalog and SystemsCatalogExtra.
 * The facts come from the system's README and test suite. Limits are stated on purpose: no tax e-invoice or e-receipt connector,
 * whole-number quantities, no stock reservation, no customer advance payments, no supplier returns, demo data is fake.
 * Online payments, WhatsApp Business and the Shopify / WooCommerce / Salla sync use the company's own accounts and were
 * tested only against simulated answers, never against a real gateway, Meta or a real store.
 */
class SystemsCatalogWholesale
{
    private const EINVOICE_AR = 'لا. النظام مبيبعتش أي حاجة لمصلحة الضرائب. الفواتير بين الشركات المسجلة بتتبعت في نظام الفاتورة الإلكترونية للمصلحة، وده محتاج شهادة توقيع رقمي وواجهة تانية مش موجودة في النظام. اسأل محاسبك لو نشاطك مطلوب منه الفاتورة الإلكترونية.';

    private const EINVOICE_EN = 'No. The system sends nothing to the tax authority. Invoices between registered businesses go through the authority\'s e-invoice system, which needs a digital signature certificate and a different interface that this system does not have. Ask your accountant whether your business must issue e-invoices.';

    /** @return array<int, array<string, mixed>> */
    public static function systems(): array
    {
        return [self::wholesale()];
    }

    private static function wholesale(): array
    {
        return [
            'slug' => 'wholesale-system', 'colors' => ['accent' => '#b45309', 'ink' => '#2a1503'],
            'name_en' => 'Wholesale and Distribution System', 'name_ar' => 'نظام المخازن والجملة والتوزيع',
            'tagline_en' => 'Quotes, orders, invoices, customer accounts, cheques, sales reps and stock in many warehouses.',
            'tagline_ar' => 'عروض أسعار وأوامر بيع وفواتير وحسابات عملاء وشيكات ومناديب وأرصدة مخازن.',
            'ar' => [
                'kicker' => 'الجملة والتوزيع', 'headline' => 'مين عليه كام، من قد إيه، وإيه اللي في كل مخزن، من غير دفتر.',
                'subheadline' => 'فواتير بالكرتونة والقطعة وقوائم أسعار لكل شريحة عملاء، وحسابات ديون بأعمارها، وتحصيل المناديب والشيكات ورابط دفع أونلاين، ومخزون كل مخزن متربط بمتجرك الأونلاين.',
                'who' => 'شركات الجملة والتوزيع والمخازن اللي بتبيع بالكرتونة وبالآجل وعندها مناديب مبيعات.',
                'problems_title' => 'ليه الجملة بتضيع فلوس؟',
                'problems' => [
                    ['icon' => 'file', 'title' => 'ديون على الورق', 'body' => 'مفيش حد عارف مين عليه كام ومن قد إيه، والدين القديم بيتنسي.'],
                    ['icon' => 'calculator', 'title' => 'أسعار بتتلخبط', 'body' => 'كل عميل له سعر، والمندوب بيقول سعر والمخزن بيحاسب بسعر تاني.'],
                    ['icon' => 'users', 'title' => 'مناديب من غير متابعة', 'body' => 'مفيش حد عارف المندوب باع وحصّل كام، ولا العمولة طالعة كام.'],
                ],
                'showcase_title' => 'من عرض السعر للتحصيل', 'showcase' => [
                    ['title' => 'عرض سعر وأمر بيع وفاتورة', 'body' => 'شاشة واحدة للتلاتة. العرض يتحول لأمر والأمر يتحول لفاتورة بنفس الأسعار. والفاتورة بس هي اللي بتسحب من المخزن.'],
                    ['title' => 'حساب العميل وأعمار الديون', 'body' => 'كشف حساب بالمدين والدائن والرصيد، وأعمار الدين (من 0 لـ 30 ومن 31 لـ 60 وأكتر من 61 يوم) وكام فات ميعاده، والكشف وتذكير المتأخر بيتبعتوا على واتساب برابط دفع.'],
                    ['title' => 'شيكات ومناديب', 'body' => 'سجل شيكات مستلم ومودع ومصروف ومرتد، وهدف وعمولة وزيارات لكل مندوب.'],
                ],
                'pillars_title' => 'أربع حاجات بتظبط الجملة',
                'pillars' => [
                    ['icon' => 'box', 'title' => 'قطعة وكرتونة', 'body' => 'المخزون بالقطعة، وبتبيع وتشتري وتحول بالكرتونة. والنظام بيحسب التحويل.'],
                    ['icon' => 'shield', 'title' => 'حد ائتمان', 'body' => 'لكل عميل حد ومدة ائتمان. الفاتورة اللي تعدي الحد بتتمنع إلا لو صاحب الصلاحية سمح.'],
                    ['icon' => 'refresh', 'title' => 'شيك مرتد يرجّع الدين', 'body' => 'لو الشيك ارتد، الفواتير اللي سددها بترجع مفتوحة تاني.'],
                    ['icon' => 'users', 'title' => 'المندوب يشوف عملاءه بس', 'body' => 'حساب المندوب بيشوف عملاءه وفواتيره وإيصالاته بس، ومينفعش يرفع حد الائتمان.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام الجملة',
                    'before' => [['b' => 'الديون', 't' => 'دفتر ورقي ومراجعة آخر الشهر.'], ['b' => 'الأسعار', 't' => 'كل مندوب بسعر.'], ['b' => 'المخزون', 't' => 'جرد بيوقف الشغل.']],
                    'after' => [['b' => 'الديون', 't' => 'رصيد وعمر دين لكل عميل وتذكير واتساب برابط دفع.'], ['b' => 'الأسعار', 't' => 'قائمة أسعار بتتحدد بالعميل.'], ['b' => 'المخزون', 't' => 'جرد بيحافظ على مبيعات وقت العد.']]],
                'metrics' => [['value' => '3', 'label' => 'قوائم أسعار في النسخة التجريبية'], ['value' => '4', 'label' => 'حالات للشيك'], ['value' => '176', 'label' => 'اختبار آلي في الكود، واختبارات متصفح']],
                'paragraphs' => ['المخزون بيتحفظ بالقطعة، والكرتونة معناها عدد قطع. تقدر تبيع وتشتري وتحول بالقطعة أو بالكرتونة. سعر الكرتونة بيتحسب من سعر القطعة، فمفيش فرق قرش في الإجمالي. المديونية بتتحسب دايمًا من الفواتير والمدفوعات ومش بتتخزن، فكشف الحساب وقائمة العملاء وأعمار الديون كلها بتديك نفس الرقم.', 'التحصيل بيتوزع على أقدم فواتير العميل الأول. المرتجع بيرجع البضاعة للمخزن اللي تختاره وبيقلل المطلوب (والخصم بيتوزع)، ولو العميل كان دافع أكتر بيتسجل رد مبلغ. وجرد المخزون بيحافظ على المبيعات اللي تمت وقت العد. الدفع الأونلاين بيبقى سند قبض عادي على أقدم الفواتير، وطلبات متجر Shopify أو WooCommerce أو سلة بتبقى فواتير على عميل المتجر من مخزن واحد بتختاره، بالقطعة أو بالكرتونة. فيه بيانات تجريبية خيالية عشان تجرب قبل ما تبدأ.'],
                'list' => ['عروض أسعار وأوامر بيع وفواتير في شاشة واحدة', 'بيع وشراء وتحويل بالقطعة أو بالكرتونة', 'قوائم أسعار لشرائح العملاء', 'حد ائتمان ومدة سداد لكل عميل', 'كشف حساب وأعمار ديون وفواتير متأخرة', 'تحصيل نقدي أو تحويل أو شيك على أقدم فاتورة، وسند قبض يتطبع', 'رابط دفع أونلاين لفاتورة أو لرصيد العميل (Paymob وفوري وإنستاباي)', 'كشف حساب وتذكير المتأخر على واتساب، لوحده أو برسالة جاهزة', 'ربط بمتجر Shopify أو WooCommerce أو سلة: المخزون والأسعار بتتبعت والطلبات بترجع فواتير', 'سجل شيكات مع الشيك المرتد', 'مرتجعات عملاء بالقطعة أو الكرتونة', 'مناديب بهدف شهري وعمولة وزيارات', 'مخازن متعددة، وتحويلات وجرد', 'مشتريات وموردين ومدفوعات', 'تقارير المبيعات والربح وأعمار الديون وأداء المناديب', 'طباعة A4 أو حراري 80 و58 مم، وكشف الحساب A4'],
                'faq' => [
                    ['q' => 'بيدعم الفاتورة الإلكترونية لمصلحة الضرائب؟', 'a' => self::EINVOICE_AR],
                    ['q' => 'ينفع أبيع بالكيلو؟', 'a' => 'لا. الكميات أرقام صحيحة (قطع). مفيش بيع بالوزن بكسور زي 1.5 كيلو.'],
                    ['q' => 'أمر البيع بيحجز البضاعة؟', 'a' => 'لا. عرض السعر وأمر البيع مبيمسوش المخزون. الفاتورة بس هي اللي بتسحب.'],
                    ['q' => 'العميل ينفع يدفع مقدم أكتر من اللي عليه؟', 'a' => 'لا. إيصال التحصيل مينفعش يكون أكبر من مديونية العميل، ومفيش دفعات مقدمة.'],
                    ['q' => 'بيبعت واتساب ويتربط بمتجري الأونلاين؟', 'a' => 'أيوه، بحساباتك انت. بحساب واتساب Business بتبعت كشف الحساب، وتذكير المتأخر برابط دفع بيتبعت لوحده كل يوم الساعة 10 لكل عميل مرة في الأسبوع بالكتير، والعميل لو رد بـ 1 بيوصله رصيده. من غير حساب الزرار بيفتح رسالة جاهزة. والمتجر (Shopify أو WooCommerce أو سلة) بياخد المخزون والأسعار من مخزن واحد، وطلب الدفع عند الاستلام اللي يعدي حد الائتمان بيتمنع. كل ده متجرب على ردود تجريبية بس، ومتجربش على خوادم Meta ولا على متجر حقيقي.'],
                ],
                'cta_title' => 'جاهز تنظم حسابات الجملة؟',
                'included' => SystemsCatalogOnline::included('ar', ['payments', 'whatsapp', 'store']),
                'need_body' => SystemsCatalogOnline::needBody('ar', 'لمزامنة المتجر وتذكير المتأخرين على واتساب ومتابعة الدفع أونلاين'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('ar', 'باقي فاتورة أو رصيده كله أو جزء منه'),
            ],
            'en' => [
                'kicker' => 'Wholesale and distribution', 'headline' => 'Who owes how much, since when, and what is in every warehouse, with no notebook.',
                'subheadline' => 'Invoices by the carton or the piece, a price list for each customer tier, debts with their age, rep collections, cheques and online payment links, and stock in every warehouse linked to your online store.',
                'who' => 'Wholesale, distribution and warehouse companies that sell by the carton, sell on credit and have sales reps.',
                'problems_title' => 'Why does a wholesaler lose money?',
                'problems' => [
                    ['icon' => 'file', 'title' => 'Debts on paper', 'body' => 'Nobody knows who owes how much and since when, and old debt gets forgotten.'],
                    ['icon' => 'calculator', 'title' => 'Prices that drift', 'body' => 'Every customer has a price, the rep quotes one and the warehouse bills another.'],
                    ['icon' => 'users', 'title' => 'Reps with no follow-up', 'body' => 'Nobody knows what a rep sold and collected, or what the commission is.'],
                ],
                'showcase_title' => 'From a quote to the money', 'showcase' => [
                    ['title' => 'Quote, order and invoice', 'body' => 'One screen for all three. A quote becomes an order and an order becomes an invoice at the same prices. Only the invoice takes stock.'],
                    ['title' => 'Customer account and aging', 'body' => 'A statement with debit, credit and balance, debt age (0-30, 31-60 and 61+ days) and how much is past its due date. The statement and an overdue reminder with a payment link go out on WhatsApp.'],
                    ['title' => 'Cheques and reps', 'body' => 'A register of received, deposited, cleared and bounced cheques, plus a target, commission and visits for each rep.'],
                ],
                'pillars_title' => 'Four things that put wholesale in order',
                'pillars' => [
                    ['icon' => 'box', 'title' => 'Piece and carton', 'body' => 'Stock is kept in pieces, and you sell, buy and transfer by the carton. The system does the conversion.'],
                    ['icon' => 'shield', 'title' => 'Credit limit', 'body' => 'Each customer has a limit and credit days. An invoice that passes the limit is refused unless someone with the permission allows it.'],
                    ['icon' => 'refresh', 'title' => 'A bounced cheque brings the debt back', 'body' => 'If a cheque bounces, the invoices it paid are open again.'],
                    ['icon' => 'users', 'title' => 'Reps see their own customers', 'body' => 'A rep login sees only its own customers, invoices and receipts, and cannot raise a credit limit.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the wholesale system',
                    'before' => [['b' => 'Debts', 't' => 'A paper book and a check at month end.'], ['b' => 'Prices', 't' => 'Every rep has a price.'], ['b' => 'Stock', 't' => 'A count that stops the work.']],
                    'after' => [['b' => 'Debts', 't' => 'A balance and debt age for every customer, and a WhatsApp reminder with a payment link.'], ['b' => 'Prices', 't' => 'A price list set by the customer.'], ['b' => 'Stock', 't' => 'A count that keeps sales made while counting.']]],
                'metrics' => [['value' => '3', 'label' => 'Price lists in the demo'], ['value' => '4', 'label' => 'Cheque statuses'], ['value' => '176', 'label' => 'Automated tests in the code, plus browser tests']],
                'paragraphs' => ['Stock is kept in pieces, and a carton is a number of pieces. You can sell, buy and transfer by the piece or by the carton. A carton price is worked out from the piece price, so the total never loses a penny. What a customer owes is always worked out from invoices and payments and never stored, so the statement, the customer list and the aging all show the same number.', 'A collection is spread over the customer\'s oldest invoices first. A return puts the goods back in the warehouse you choose and lowers what is owed (the discount is shared out), and if the customer had paid more, a refund is recorded. A stock take keeps the sales made while you count. An online payment becomes a normal receipt voucher on the oldest invoices, and orders from your Shopify, WooCommerce or Salla store become invoices of the store\'s customer from one warehouse you choose, by the piece or by the carton. It ships with fictional sample data so you can try it first.'],
                'list' => ['Quotes, sales orders and invoices on one screen', 'Sell, buy and transfer by the piece or by the carton', 'Price lists for customer tiers', 'A credit limit and credit days for each customer', 'A statement, debt aging and a list of overdue invoices', 'Collections by cash, transfer or cheque on the oldest invoice, with a printable receipt voucher', 'An online payment link for an invoice or the customer balance (Paymob, Fawry and InstaPay)', 'The statement and overdue reminders on WhatsApp, by themselves or as a ready message', 'Shopify, WooCommerce or Salla sync: stock and prices go out and orders come back as invoices', 'A cheque register with bounced cheques', 'Customer returns by the piece or the carton', 'Reps with a monthly target, commission and visits', 'Many warehouses, transfers and a stock take', 'Purchases, suppliers and payments', 'Reports for sales, profit, debt aging and rep performance', 'Print on A4 or thermal 80 and 58 mm, and the statement on A4'],
                'faq' => [
                    ['q' => 'Does it support the tax e-invoice?', 'a' => self::EINVOICE_EN],
                    ['q' => 'Can I sell by weight?', 'a' => 'No. Quantities are whole numbers (pieces). There is no selling by fractions such as 1.5 kg.'],
                    ['q' => 'Does a sales order reserve stock?', 'a' => 'No. A quote and an order never touch stock. Only the invoice takes it.'],
                    ['q' => 'Can a customer pay in advance, more than they owe?', 'a' => 'No. A receipt cannot be bigger than what the customer owes, and there are no advance payments.'],
                    ['q' => 'Does it send WhatsApp messages and connect to my online store?', 'a' => 'Yes, through your own accounts. With a WhatsApp Business account you send the statement, and an overdue reminder with a payment link goes out by itself every day at 10:00, to each customer at most once a week; a customer who replies 1 gets the balance back. Without an account the button opens a ready message. The store (Shopify, WooCommerce or Salla) takes stock and prices from one warehouse, and a cash on delivery order over the credit limit is refused. All of this is tested against simulated answers only, not against Meta\'s servers or a real store.'],
                ],
                'cta_title' => 'Ready to put your wholesale accounts in order?',
                'included' => SystemsCatalogOnline::included('en', ['payments', 'whatsapp', 'store']),
                'need_body' => SystemsCatalogOnline::needBody('en', 'for the online-store sync, WhatsApp overdue reminders and online payment checks'),
                'payments_faq' => SystemsCatalogOnline::paymentsFaq('en', 'what is left on an invoice or all or part of their balance'),
            ],
        ];
    }
}
