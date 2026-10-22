<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Shop Manager Pro: an offline invoices and stock program for one small shop on one Windows computer.
 * The facts come from the program README and its tests. The license check needs the internet at start,
 * so the page says "works offline after the check, with a 14 day grace" and never "fully offline".
 * It has no tax e-invoice or e-receipt connector, no cloud sync and no user accounts, and the copy says so.
 */
class ShopManagerCatalog
{
    public static function program(): array
    {
        return [
            'slug' => 'shop-manager-pro',
            'sort_order' => 8,
            'image_path' => 'images/software/shop-manager-pro.svg',
            'name_en' => 'Shop Manager Pro',
            'name_ar' => 'مدير المحل برو',
            'tagline_en' => 'Simple invoices and stock for a small shop. Sell, buy, return, print and back up, on your own computer.',
            'tagline_ar' => 'فواتير ومخزون بسيطين لمحل صغير. بيع وشراء ومرتجع وطباعة ونسخ احتياطي، على جهازك أنت.',
            'description_en' => 'A Windows program for a grocery, stationery, spare-parts or clothes shop: products and barcodes, a fast sales screen, customer debts, suppliers, returns, a daily profit report and printed invoices.',
            'description_ar' => 'برنامج ويندوز لمحل بقالة أو أدوات مكتبية أو قطع غيار أو ملابس: أصناف وباركود، وشاشة بيع سريعة، وديون العملاء، والموردين، والمرتجع، وتقرير ربح يومي، وفواتير مطبوعة.',
            'price' => 1500,
            'currency' => 'EGP',
            'theme' => [
                'colors' => ['accent' => '#0f766e', 'ink' => '#06241f'],
                'sections' => ['problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery'],
            ],
            'landing' => ['ar' => self::arabic(), 'en' => self::english()],
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'برنامج محل ومخزون',
            'headline' => 'بيع واشتري وخزّن وطبع فواتيرك من برنامج واحد بسيط، على جهازك.',
            'subheadline' => 'أصناف وباركود، وشاشة بيع سريعة، وديون العملاء ومستحقات الموردين، ومرتجع، وتقرير ربح لليوم. بياناتك في ملف على جهازك، ومش محتاج نظام ويب.',
            'overview' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                ['title' => 'مناسب لمين؟', 'body' => 'محل بقالة أو سوبر ماركت صغير أو أدوات مكتبية أو قطع غيار أو ملابس، على جهاز كمبيوتر واحد، وعايز يسيب الورق والدفتر.'],
                ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 (64 بت). الإنترنت مطلوب وقت التفعيل وفتح البرنامج. بعد كده البيع والشراء والتقارير بيشتغلوا من غير إنترنت.'],
                ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، ولو فتحت صفحة الشراء من نافذة البرنامج بيتفعل جهازك فورا. وبتوصلك رسالة واتساب جاهزة برقم طلبك.'],
            ]],
            'problems' => ['kicker' => 'مشاكل المحل الصغير', 'title' => 'ليه دفتر الحسابات بيتعب؟', 'items' => [
                ['icon' => 'clock', 'title' => 'جرد وحسابات بالإيد', 'body' => 'آخر اليوم بتجمع وتطرح، وفي الآخر مش عارف الربح الحقيقي.'],
                ['icon' => 'box', 'title' => 'بضاعة بتخلص فجأة', 'body' => 'مفيش حد بيقولك إن صنف قرّب يخلص إلا لما العميل يسأل ومتلاقيهوش.'],
                ['icon' => 'users', 'title' => 'ديون منسية', 'body' => 'مين عليه كام؟ ومين دفع جزء؟ الدفتر بيضيع والذاكرة بتخون.'],
            ]],
            'stat' => ['kicker' => 'شغال من غير إنترنت', 'value' => '14', 'title' => 'يوم تشغيل بدون إنترنت بعد آخر تأكيد للترخيص', 'body' => 'لو الإنترنت قطع، البرنامج بيفتح ويشتغل لحد 14 يوم من آخر مرة السيرفر أكّد فيها الترخيص. بعدها لازم يتصل مرة. وأول تفعيل على الجهاز لازم يكون بإنترنت.'],
            'showcase' => ['kicker' => 'شاشات البرنامج', 'title' => 'من الكاشير للتقرير', 'items' => [
                ['title' => 'شاشة البيع', 'body' => 'امسح الباركود أو اكتب اسم الصنف، وعدّل الكمية والسعر، واحسب الخصم.'],
                ['title' => 'العملاء والموردين', 'body' => 'رصيد كل واحد وكشف حساب، وتحصيل دفعات من الحساب.'],
                ['title' => 'التقارير', 'body' => 'مبيعات اليوم والربح والنقدية، وقيمة المخزون، والنواقص.'],
            ]],
            'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز لمحل منظم', 'items' => [
                ['icon' => 'check', 'title' => 'حسابات دقيقة', 'body' => 'الفلوس بتتحسب بالقروش والكميات بالجرام، فمفيش كسور بتضيع. وكل فاتورة بتتحفظ كاملة أو مبتتحفظش خالص.'],
                ['icon' => 'box', 'title' => 'مخزون واضح', 'body' => 'كل بيع وشراء ومرتجع بيحدّث المخزون. حد أدنى لكل صنف وقايمة نواقص، وجرد بيتسجل فرقه.'],
                ['icon' => 'users', 'title' => 'ديون وموردين', 'body' => 'بيع آجل أو بدفع جزء، والدفعة بتسدد أقدم فاتورة الأول، والبرنامج بيرفض دفعة أكبر من الرصيد.'],
                ['icon' => 'shield', 'title' => 'نسخ احتياطي', 'body' => 'نسخة يومية تلقائية وبتحتفظ بآخر 14، واستعادة بضغطة. النسخة اللي على نفس الجهاز بتضيع لو الجهاز ضاع، فانسخها على فلاشة.'],
            ]],
            'video' => ['kicker' => 'شوف بنفسك', 'title' => 'جولة سريعة جوه البرنامج'],
            'steps' => ['kicker' => 'خطوات الاستخدام', 'title' => 'من أول فتح لأول فاتورة في 4 خطوات', 'items' => [
                ['title' => 'اكتب بيانات المحل', 'body' => 'الاسم والعنوان والتليفون والرقم الضريبي والشعار، عشان تظهر على الفاتورة.'],
                ['title' => 'ضيف أصنافك', 'body' => 'واحد واحد أو استورد ملف إكسل أو CSV فيه الأصناف.'],
                ['title' => 'ابدأ البيع', 'body' => 'امسح الباركود أو اكتب الاسم، واحفظ الفاتورة، واطبع إيصال أو A4.'],
                ['title' => 'راجع آخر اليوم', 'body' => 'افتح التقرير اليومي وشوف المبيعات والربح والنقدية والنواقص.'],
            ]],
            'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد البرنامج', 'before_title' => 'بالدفتر والورقة', 'after_title' => 'مع مدير المحل برو',
                'before' => [['b' => 'الحساب', 't' => 'جمع وطرح بالإيد في آخر اليوم.'], ['b' => 'المخزون', 't' => 'جرد تقديري.'], ['b' => 'الديون', 't' => 'دفتر بيتبعثر.']],
                'after' => [['b' => 'الحساب', 't' => 'تقرير يوم جاهز في ثواني.'], ['b' => 'المخزون', 't' => 'رصيد كل صنف ونواقص.'], ['b' => 'الديون', 't' => 'رصيد وكشف حساب لكل عميل.']]],
            'metrics' => ['kicker' => 'ملخص', 'title' => 'البرنامج في أرقام', 'items' => [
                ['value' => '14', 'label' => 'يوم تشغيل بدون إنترنت بعد آخر تأكيد'], ['value' => '2', 'label' => 'مقاسين طباعة: إيصال 80 مم و A4'], ['value' => '14', 'label' => 'نسخة احتياطية محفوظة تلقائي']]],
            'gallery' => ['title' => 'صور من البرنامج'],
            'details' => ['title' => 'تفاصيل البرنامج', 'paragraphs' => [
                'مدير المحل برو برنامج ويندوز بيشتغل على جهاز واحد، وبيخزّن كل بياناتك في ملف واحد على الجهاز. من غير سيرفر ومن غير اشتراك إنترنت للبيع نفسه. الواجهة عربي وإنجليزي ويمين لشمال وشمال ليمين.',
                'البرنامج للمحلات الصغيرة: مفيش حسابات للكاشير ولا صلاحيات، ومفيش مزامنة بين أكتر من جهاز. لو محتاج ده، ده مش البرنامج المناسب.',
            ], 'list_title' => 'اللي هتحصل عليه', 'list' => [
                'أصناف بالاسم والباركود والوحدة والقسم والتكلفة والسعر والمخزون والحد الأدنى', 'شاشة بيع بالباركود أو الاسم، وكميات بالكسور زي 0.75 كجم، وخصم، وبيع آجل أو بدفع جزء',
                'مشتريات من الموردين، وتكلفة الصنف بتبقى آخر سعر شراء', 'عملاء وموردين برصيد وكشف حساب وتحصيل دفعات', 'مرتجع بيع ومرتجع شراء، بيخصم من الدين الأول وبعدين نقدي',
                'تقرير يومي: مبيعات ومرتجع وتكلفة ومجمل ربح ومشتريات ونقدية', 'قيمة المخزون بالتكلفة وبسعر البيع وقايمة النواقص', 'طباعة إيصال 80 مم أو فاتورة A4 باسم محلك وشعارك',
                'استيراد الأصناف من إكسل أو CSV بعناوين عربي أو إنجليزي', 'تصدير التقارير إكسل أو CSV', 'نسخ احتياطي تلقائي يومي واستعادة', 'واجهة عربي وإنجليزي وتحديث تلقائي',
            ]],
            'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                ['q' => 'هل بيشتغل من غير إنترنت فعلا؟', 'a' => 'البيع والشراء والتقارير والطباعة بتشتغل من غير إنترنت. لكن فحص الترخيص محتاج إنترنت: أول تفعيل لازم يكون بإنترنت، وبعد كده لو الإنترنت قطع البرنامج بيفتح لحد 14 يوم من آخر مرة اتأكد فيها الترخيص. بعد المدة دي لازم يتصل مرة ويتأكد.'],
                ['q' => 'هل بيرسل فواتير للضرايب (الفاتورة أو الإيصال الإلكتروني)؟', 'a' => 'لا. البرنامج ده بيطبع إيصال أو فاتورة بس، ومفيهوش ربط مع مصلحة الضرائب. لو نشاطك ملزم بالإيصال الإلكتروني اسأل المحاسب، وهتحتاج نظام مربوط بالمصلحة.'],
                ['q' => 'ينفع أكتر من جهاز على نفس البيانات؟', 'a' => 'لا. الترخيص لجهاز واحد، والبيانات على الجهاز ده بس. مفيش مزامنة سحابية ولا شبكة بين أجهزة.'],
                ['q' => 'لو الجهاز اتسرق أو اتعطل؟', 'a' => 'النسخة الاحتياطية المحفوظة على نفس الجهاز بتضيع معاه. انسخ مجلد النسخ الاحتياطي على فلاشة أو جهاز تاني من وقت لآخر. النسخ الاحتياطي مسؤوليتك.'],
                ['q' => 'ينفع أكتر من كاشير بحسابات وصلاحيات؟', 'a' => 'لا. اللي بيفتح البرنامج بيقدر يعمل كل حاجة. ده برنامج بسيط لمحل بيديره شخص أو اتنين على جهاز واحد.'],
                ['q' => 'بيشتغل مع قارئ الباركود والطابعة؟', 'a' => 'قارئ الباركود اللي بيكتب الأرقام زي الكيبورد ويضغط Enter بيشتغل مع خانة البحث. الطباعة بتتم من نافذة الطباعة العادية في ويندوز، فأي طابعة معرّفة على الجهاز تنفع، وجهّز مقاس الورق من إعدادات الطابعة. لم نختبر موديلات طابعات أو قارئات محددة.'],
                ['q' => 'أنقل أصنافي من ملف إكسل؟', 'a' => 'أيوه. الصف الأول عناوين الأعمدة (الاسم، الباركود، سعر الشراء، سعر البيع، الكمية). الصفوف اللي فيها مشكلة بتتخطى وبتتعرض لك، والباقي بيتضاف، وبيتعمل نسخة احتياطية قبل الاستيراد.'],
                ['q' => 'بيشتغل على ماك؟', 'a' => 'لا. ويندوز 10 أو 11 (64 بت) بس.'],
                ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
            ]],
            'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تسيب الدفتر؟', 'body' => 'ادفع مرة واحدة في السنة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري مدير المحل برو'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'Shop and stock program',
            'headline' => 'Sell, buy, keep stock and print invoices from one simple program on your own computer.',
            'subheadline' => 'Products and barcodes, a fast sales screen, customer debts and supplier balances, returns, and a daily profit report. Your data sits in one file on your computer, with no web system needed.',
            'overview' => ['title' => 'Understand everything in one minute', 'items' => [
                ['title' => 'Who is it for?', 'body' => 'A grocery, a small supermarket, a stationery, spare-parts or clothes shop, on one computer, that wants to leave the paper and the notebook.'],
                ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 (64-bit) computer. The internet is needed to activate and to open the program. After that, selling, buying and reports work without internet.'],
                ['title' => 'What happens after payment?', 'body' => 'After payment your account and license are created automatically, and if you opened the buy page from the program window your device is activated right away. You also get a ready WhatsApp message with your order number.'],
            ]],
            'problems' => ['kicker' => 'The small shop problems', 'title' => 'Why does the account book wear you out?', 'items' => [
                ['icon' => 'clock', 'title' => 'Counting by hand', 'body' => 'At the end of the day you add and subtract, and still do not know the real profit.'],
                ['icon' => 'box', 'title' => 'Goods that run out suddenly', 'body' => 'Nobody tells you an item is nearly gone until a customer asks and you do not have it.'],
                ['icon' => 'users', 'title' => 'Forgotten debts', 'body' => 'Who owes how much? Who paid a part? The notebook gets lost and memory fails.'],
            ]],
            'stat' => ['kicker' => 'Works without internet', 'value' => '14', 'title' => 'Days it can start offline after the last licence confirmation', 'body' => 'If the internet is down, the program still opens and works for up to 14 days after the last time the server confirmed the license. After that it must connect once. The first activation on a device needs the internet.'],
            'showcase' => ['kicker' => 'Program screens', 'title' => 'From the counter to the report', 'items' => [
                ['title' => 'The sales screen', 'body' => 'Scan a barcode or type a name, change the quantity and price, and apply a discount.'],
                ['title' => 'Customers and suppliers', 'body' => 'Each balance and statement, and payments taken on account.'],
                ['title' => 'Reports', 'body' => 'Sales of the day, profit and cash, stock value, and what is low.'],
            ]],
            'pillars' => ['kicker' => 'What the program can do', 'title' => 'Four pillars of an organized shop', 'items' => [
                ['icon' => 'check', 'title' => 'Exact accounts', 'body' => 'Money is counted in piastres and quantities in thousandths, so no fractions are lost. Every invoice is saved whole or not at all.'],
                ['icon' => 'box', 'title' => 'Clear stock', 'body' => 'Every sale, purchase and return updates the stock. A minimum for each item, a low-stock list, and stock counts that record the difference.'],
                ['icon' => 'users', 'title' => 'Debts and suppliers', 'body' => 'Credit sales or part payments. A payment settles the oldest invoice first, and the program refuses a payment bigger than the balance.'],
                ['icon' => 'shield', 'title' => 'Backups', 'body' => 'A daily automatic backup that keeps the last 14, and a one-click restore. A backup on the same computer is lost if the computer is lost, so copy it to a flash drive.'],
            ]],
            'video' => ['kicker' => 'See it yourself', 'title' => 'A quick tour inside the program'],
            'steps' => ['kicker' => 'How to use it', 'title' => 'From first launch to first invoice in 4 steps', 'items' => [
                ['title' => 'Enter your shop details', 'body' => 'Name, address, phone, tax number and logo, so they appear on the invoice.'],
                ['title' => 'Add your products', 'body' => 'One by one, or import an Excel or CSV file of products.'],
                ['title' => 'Start selling', 'body' => 'Scan a barcode or type a name, save the invoice, and print a receipt or an A4 page.'],
                ['title' => 'Review the day', 'body' => 'Open the daily report to see sales, profit, cash and what is low.'],
            ]],
            'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the program', 'before_title' => 'With a notebook and paper', 'after_title' => 'With Shop Manager Pro',
                'before' => [['b' => 'Accounts', 't' => 'Adding and subtracting by hand at day end.'], ['b' => 'Stock', 't' => 'A rough count.'], ['b' => 'Debts', 't' => 'A notebook that gets scattered.']],
                'after' => [['b' => 'Accounts', 't' => 'A day report ready in seconds.'], ['b' => 'Stock', 't' => 'The balance of each item and a low-stock list.'], ['b' => 'Debts', 't' => 'A balance and a statement for each customer.']]],
            'metrics' => ['kicker' => 'Summary', 'title' => 'The program in numbers', 'items' => [
                ['value' => '14', 'label' => 'Days it can start offline after the last confirmation'], ['value' => '2', 'label' => 'Print sizes: 80 mm receipt and A4'], ['value' => '14', 'label' => 'Backups kept automatically']]],
            'gallery' => ['title' => 'Pictures of the program'],
            'details' => ['title' => 'Program details', 'paragraphs' => [
                'Shop Manager Pro is a Windows program that runs on one computer and keeps all your data in one file on that computer. There is no server, and selling needs no internet subscription. The interface is Arabic and English, right to left and left to right.',
                'It is made for small shops: there are no cashier accounts or permissions, and no sync between more than one computer. If you need that, this is not the right program.',
            ], 'list_title' => 'What you get', 'list' => [
                'Products with name, barcode, unit, category, cost, price, stock and minimum stock', 'A sales screen by barcode or name, quantities with fractions such as 0.75 kg, a discount, and credit or part-paid sales',
                'Purchases from suppliers, with the product cost set to the last purchase price', 'Customers and suppliers with balances, statements and payments on account', 'Sales returns and purchase returns that clear the debt first and then pay cash',
                'A daily report: sales, returns, cost, gross profit, purchases and cash', 'Stock value at cost and at selling price, and a low-stock list', 'Printing a 80 mm receipt or an A4 invoice with your shop name and logo',
                'Import products from Excel or CSV with Arabic or English column titles', 'Export reports to Excel or CSV', 'A daily automatic backup and restore', 'An Arabic and English interface and automatic updates',
            ]],
            'faq' => ['title' => 'Common questions', 'items' => [
                ['q' => 'Does it really work without internet?', 'a' => 'Selling, buying, reports and printing work without internet. The license check needs the internet: the first activation must be online, and after that, if the internet is down, the program opens for up to 14 days after the last time the license was confirmed. After that period it must connect once.'],
                ['q' => 'Does it send tax invoices or e-receipts to the authority?', 'a' => 'No. This program only prints a receipt or an invoice. It has no connection to the Egyptian Tax Authority. If your business must issue e-receipts, ask your accountant, and you will need a system that is connected to the authority.'],
                ['q' => 'Can more than one computer use the same data?', 'a' => 'No. The license is for one device and the data stays on that device. There is no cloud sync and no network between computers.'],
                ['q' => 'What if the computer is stolen or breaks?', 'a' => 'A backup saved on the same computer is lost with it. Copy the backup folder to a flash drive or another computer now and then. Backups are your responsibility.'],
                ['q' => 'Can I have several cashiers with accounts and permissions?', 'a' => 'No. Whoever opens the program can do everything. It is a simple program for a shop run by one or two people on one computer.'],
                ['q' => 'Does it work with a barcode scanner and a printer?', 'a' => 'A barcode scanner that types the digits like a keyboard and presses Enter works with the search box. Printing goes through the normal Windows print window, so any printer installed on the computer can be used, and you set the paper size in the printer settings. We have not tested specific printer or scanner models.'],
                ['q' => 'Can I move my products from an Excel file?', 'a' => 'Yes. The first row holds column titles (name, barcode, cost, price, stock). Rows with a problem are skipped and listed for you, the rest are added, and a backup is made before the import.'],
                ['q' => 'Does it run on Mac?', 'a' => 'No. Windows 10 or 11 (64-bit) only.'],
                ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
            ]],
            'cta' => ['kicker' => 'Start today', 'title' => 'Ready to leave the notebook?', 'body' => 'Pay once a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Shop Manager Pro'],
        ];
    }
}
