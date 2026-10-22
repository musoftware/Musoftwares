<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * The supermarket system. Same shape as the systems in SystemsCatalog (add `SystemsCatalogSupermarket::system()` to its systems() list).
 * The facts come from the system's README, seeder and test suite. Limits are stated on purpose: the ETA e-receipt connector is built to the
 * documentation but not tested against the real ETA, no scale or scanner was connected (only keyboard-type scanners are expected to work),
 * no batches or first-expiry-first-out, no online payment, demo data is fake.
 */
class SystemsCatalogSupermarket
{
    private const ETA_AR = 'أيوه، فيه ربط جاهز بالإيصال الإلكتروني لمصلحة الضرائب (ETA)، والبيع بالوزن بيتبعت بوحدة الكيلو أو اللتر وبكسره الحقيقي. كتبناه على التوثيق الرسمي للمصلحة واختبرناه على خادم تجريبي بس، ومجربناهوش على خادم المصلحة الحقيقي ومعندناش اعتماد منها. عشان تشغله لازم تسجّل جهاز الكاشير في بوابة المصلحة وتحصل على بياناته وأكواد الأصناف، وتجرّبه على بيئة التجربة الأول. اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني.';

    private const ETA_EN = 'Yes, a connector for the Egyptian Tax Authority e-receipt (ETA) is included, and weighed goods are sent with the kilo or litre unit and their real fraction. It is written to ETA\'s public documentation and tested only against a simulated server: it has not been tested against the real ETA and it is not certified by ETA. To use it you register your cash register in the ETA portal, get its credentials and your item codes, and try it on the ETA test environment first. Ask your accountant whether your business must issue e-receipts.';

    /** @return array<string, mixed> */
    public static function system(): array
    {
        return [
            'slug' => 'supermarket-system', 'colors' => ['accent' => '#15803d', 'ink' => '#05230f'],
            'name_en' => 'Supermarket Management System', 'name_ar' => 'نظام إدارة السوبر ماركت',
            'tagline_en' => 'Barcode point of sale, weighed goods, cashier shifts and stock for a supermarket or grocery shop.',
            'tagline_ar' => 'كاشير بالباركود وبيع بالوزن وورديات كاشير ومخزون لسوبر ماركت أو بقالة.',
            'ar' => [
                'kicker' => 'السوبر ماركت والبقالة', 'headline' => 'كاشير سريع، ودرج فلوس بيتعد كل وردية.',
                'subheadline' => 'الكاشير بيمسح الباركود أو يكتب الوزن، والفاتورة بتتعلّق لو الزبون نسي حاجة، وكل كاشير بيقفل ورديته بالفلوس اللي عدّها والنظام يوريك الفرق.',
                'who' => 'أصحاب السوبر ماركت والبقالة والمحلات اللي بتبيع بالباركود وبالوزن (جبنة، خضار، لحوم) وعندها كاشير أو أكتر، وفرع واحد أو أكتر.',
                'problems_title' => 'ليه المحل بيخسر من غير ما تحس؟',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'فلوس الدرج ناقصة', 'body' => 'مفيش وردية ولا عدّ للدرج، فلما الفلوس تنقص محدش يعرف مين ولا امتى.'],
                    ['icon' => 'box', 'title' => 'الوزن والكسور', 'body' => 'البيع بالكيلو بالورقة والآلة الحاسبة بيعمل أخطاء، والمخزون بيبوظ مع الكسور.'],
                    ['icon' => 'file', 'title' => 'الآجل والموردين', 'body' => 'ديون الزبائن وحسابات الموردين في كشكول، والنواقص بتتعرف لما الرف يفضى.'],
                ],
                'showcase_title' => 'الكاشير والوردية والمخزون', 'showcase' => [
                    ['title' => 'كاشير بالباركود', 'body' => 'بحث بالباركود أو الاسم، وتطابق الباركود الكامل بيطلع أول، وخصم، وبيع آجل بدفعات، وطباعة 80 مم أو 58 مم أو A4.'],
                    ['title' => 'وردية الكاشير', 'body' => 'فتح بالنقدية اللي في الدرج، وقفل بالنقدية المعدودة، والنظام بيحسب المتوقع ويحفظ الفرق. والبيع بيحتاج وردية مفتوحة.'],
                    ['title' => 'مخزون وتنبيهات', 'body' => 'نواقص حسب حد إعادة الطلب لكل منتج، وتنبيهات تاريخ الانتهاء (منتهي و30 و60 و90 يوم) بقيمة التكلفة.'],
                ],
                'pillars_title' => 'أربع حاجات تظبط شغل المحل',
                'pillars' => [
                    ['icon' => 'zap', 'title' => 'بيع بالوزن', 'body' => 'الكمية بتتكتب بثلاث خانات عشرية (0.25 كيلو)، والمخزون بيفضل مظبوط حتى بعد عشر عمليات بيع.'],
                    ['icon' => 'clock', 'title' => 'فواتير معلّقة', 'body' => 'علّق الفاتورة والزبون يجيب حاجة تانية، وكل كاشير يشوف الفواتير اللي علّقها هو بس.'],
                    ['icon' => 'refresh', 'title' => 'مرتجعات وتحويلات', 'body' => 'مرتجع عميل بالكمية الجزئية، ومرتجع مورد، وتحويل بين الفروع، وتسوية مخزون وإعدام للمنتهي.'],
                    ['icon' => 'users', 'title' => 'صلاحيات', 'body' => 'كاشير وأمين مخزن ومدير ومالك، وكل واحد يشوف شغله بس.'],
                ],
                'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد النظام', 'before_title' => 'الطريقة التقليدية', 'after_title' => 'مع نظام السوبر ماركت',
                    'before' => [['b' => 'الدرج', 't' => 'فلوس بتنقص ومحدش يعرف السبب.'], ['b' => 'الوزن', 't' => 'حسابات بالورقة وأخطاء في الكسور.'], ['b' => 'الموردين', 't' => 'ديون في كشكول.']],
                    'after' => [['b' => 'الدرج', 't' => 'عدّ في بداية ونهاية كل وردية وفرق متسجل.'], ['b' => 'الوزن', 't' => 'كمية بثلاث خانات ومخزون مظبوط.'], ['b' => 'الموردين', 't' => 'رصيد كل مورد ومرتجعاته.']]],
                'metrics' => [['value' => '65', 'label' => 'منتج تجريبي في النسخة التجريبية'], ['value' => '3', 'label' => 'أحجام طباعة للإيصال'], ['value' => '176', 'label' => 'اختبار آلي في الكود']],
                'paragraphs' => ['النظام بيحفظ المخزون لكل منتج في كل فرع، والكمية بتتسجل بثلاث خانات عشرية. المنتج الموزون ممكن يتباع بالكسر (0.25 كيلو)، والمنتج بالقطعة لازم يتباع بوحدات كاملة. تكلفة كل بند بتتسجل وقت البيع، فالربح بيفضل مظبوط حتى لو السعر اتغير.', 'جوه النظام: كاشير بالباركود، وفواتير معلّقة، وورديات الكاشير، والمنتجات والمخزون، والمشتريات والموردين، والعملاء والآجل، وتحويلات الفروع، وتسويات المخزون، وملصقات الأسعار بباركود، وتقارير يومية. فيه بيانات تجريبية خيالية عشان تجرب قبل ما تبدأ.'],
                'list' => ['كاشير بالباركود والاسم وبيع آجل بدفعات', 'بيع بالوزن بكسور (كيلو ولتر)', 'فواتير معلّقة لكل كاشير', 'ورديات الكاشير: نقدية الفتح والعدّ والفرق', 'مخزون لكل فرع، ونواقص حسب حد إعادة الطلب', 'تاريخ انتهاء اختياري لكل منتج وتنبيهات 30 و60 و90 يوم', 'مشتريات وموردين ومرتجعات وتحويلات بين الفروع وتسويات مخزون', 'ملصقات أسعار بباركود وإيصالات 80 و58 مم وA4', 'تقارير المبيعات والربح وأفضل المنتجات والورديات والديون', 'إرسال المبيعات والمرتجعات للإيصال الإلكتروني (ETA) لكل فرع، ويحتاج بيانات مسجّلة عندك'],
                'faq' => [
                    ['q' => 'بيدعم الإيصال الإلكتروني للضرائب؟', 'a' => self::ETA_AR],
                    ['q' => 'بيشتغل مع ميزان وقارئ باركود؟', 'a' => 'قارئات الباركود اللي بتكتب زي لوحة المفاتيح المفروض تشتغل، لكن ما جربناش موديلات محددة. مفيش تعريف لميزان إلكتروني، والكاشير بيكتب الوزن، وباركود الميزان اللي فيه الوزن أو السعر مش بيتفك.'],
                    ['q' => 'فيه دفعات وصلاحية بالأقرب انتهاءً؟', 'a' => 'لا. فيه تاريخ انتهاء واحد اختياري لكل منتج مع تنبيهات، ومفيش دفعات ولا بيع بالأقرب انتهاءً.'],
                    ['q' => 'البيانات التجريبية حقيقية؟', 'a' => 'لا، كل المنتجات والعملاء والمبيعات في النسخة التجريبية خيالية.'],
                ],
                'cta_title' => 'جاهز تنظم سوبر ماركتك؟',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'طرق الدفع المسجلة: نقدي وبطاقة وتحويل ومحفظة (تسجيل فقط، من غير دفع أونلاين)'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => 'لا. النظام بيسجل طريقة الدفع (نقدي أو بطاقة أو تحويل أو محفظة) لكنه مبيحصلش الدفع بنفسه.'],
            ],
            'en' => [
                'kicker' => 'Supermarkets and grocery shops', 'headline' => 'A fast cashier, and a cash drawer that is counted every shift.',
                'subheadline' => 'The cashier scans the barcode or types the weight, a sale can be parked when the customer forgot something, and every cashier closes the shift with the cash they counted, so the system shows any difference.',
                'who' => 'Owners of supermarkets, grocery and shops that sell by barcode and by weight (cheese, vegetables, meat), with one or more cashiers and one or more branches.',
                'problems_title' => 'Why does the shop lose money without you noticing?',
                'problems' => [
                    ['icon' => 'calculator', 'title' => 'The drawer is short', 'body' => 'No shifts and no drawer count, so when cash is missing nobody knows who or when.'],
                    ['icon' => 'box', 'title' => 'Weight and fractions', 'body' => 'Selling by the kilo with paper and a calculator causes mistakes, and stock breaks with fractions.'],
                    ['icon' => 'file', 'title' => 'Credit and suppliers', 'body' => 'Customer credit and supplier accounts live in a notebook, and shortages show up when the shelf is empty.'],
                ],
                'showcase_title' => 'Cashier, shift and stock', 'showcase' => [
                    ['title' => 'Barcode cashier', 'body' => 'Search by barcode or name, an exact barcode match comes first, discounts, credit sales with part payments, and print on 80 mm, 58 mm or A4.'],
                    ['title' => 'Cashier shift', 'body' => 'Open with the cash in the drawer, close with the counted cash, and the system works out the expected cash and keeps the difference. Selling needs an open shift.'],
                    ['title' => 'Stock and alerts', 'body' => 'Shortage alerts from the reorder level of each product, and expiry alerts (expired, 30, 60 and 90 days) valued at cost.'],
                ],
                'pillars_title' => 'Four things that put the shop in order',
                'pillars' => [
                    ['icon' => 'zap', 'title' => 'Selling by weight', 'body' => 'Quantities have three decimals (0.25 kg), and stock stays exact even after ten sales.'],
                    ['icon' => 'clock', 'title' => 'Parked sales', 'body' => 'Park a sale while the customer fetches something else. Each cashier sees only the sales they parked.'],
                    ['icon' => 'refresh', 'title' => 'Returns and transfers', 'body' => 'Customer returns in part quantities, supplier returns, branch transfers, stock adjustments and write-off of expired goods.'],
                    ['icon' => 'users', 'title' => 'Roles', 'body' => 'Cashier, stock keeper, manager and owner, each sees their own work.'],
                ],
                'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the system', 'before_title' => 'The old way', 'after_title' => 'With the supermarket system',
                    'before' => [['b' => 'Drawer', 't' => 'Cash goes missing and nobody knows why.'], ['b' => 'Weight', 't' => 'Paper sums and mistakes with fractions.'], ['b' => 'Suppliers', 't' => 'Debts in a notebook.']],
                    'after' => [['b' => 'Drawer', 't' => 'Counted at the start and end of every shift, with the difference saved.'], ['b' => 'Weight', 't' => 'Three-decimal quantities and exact stock.'], ['b' => 'Suppliers', 't' => 'Each supplier balance and returns.']]],
                'metrics' => [['value' => '65', 'label' => 'Sample products in the demo'], ['value' => '3', 'label' => 'Receipt print sizes'], ['value' => '176', 'label' => 'Automated tests in the code']],
                'paragraphs' => ['Stock is kept per product in each branch, with a quantity of three decimals. A weighed product can be sold in fractions (0.25 kg) while a piece product must be sold in whole units. The cost of every line is saved when it is sold, so profit stays correct even when prices change.', 'Inside: a barcode cashier, parked sales, cashier shifts, products and stock, purchases and suppliers, customers and credit, branch transfers, stock adjustments, barcode price labels and daily reports. It ships with fictional sample data so you can try it first.'],
                'list' => ['Barcode and name cashier with credit sales and part payments', 'Selling by weight in fractions (kilo and litre)', 'Parked sales for each cashier', 'Cashier shifts: opening cash, count and difference', 'Stock per branch and shortage alerts from the reorder level', 'An optional expiry date on each product with 30, 60 and 90 day alerts', 'Purchases, suppliers, returns, branch transfers and stock adjustments', 'Barcode price labels and 80 mm, 58 mm and A4 receipts', 'Sales, profit, best-seller, shift and debt reports', 'Sales and returns sent to the tax e-receipt (ETA), one cash register per branch (needs your own registration)'],
                'faq' => [
                    ['q' => 'Does it support the tax e-receipt?', 'a' => self::ETA_EN],
                    ['q' => 'Does it work with a scale and a barcode scanner?', 'a' => 'Barcode scanners that type like a keyboard are expected to work, but we did not test specific models. There is no driver for an electronic scale: the cashier types the weight, and scale barcodes that contain the weight or price are not decoded.'],
                    ['q' => 'Does it have batches and first-expiry-first-out?', 'a' => 'No. There is one optional expiry date per product with alerts. There are no batches and no first-expiry-first-out selling.'],
                    ['q' => 'Is the demo data real?', 'a' => 'No. All products, customers and sales in the demo are fictional.'],
                ],
                'cta_title' => 'Ready to organize your supermarket?',
                'included' => ['Arabic and English with right-to-left support', 'Recorded payment methods: cash, card, transfer and wallet (recorded only, no online payment)'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => 'No. The system records the payment method (cash, card, transfer or wallet) but does not collect the payment itself.'],
            ],
        ];
    }
}
