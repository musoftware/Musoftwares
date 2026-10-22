<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Email Finder Pro: a desktop program that finds public business emails on company websites and checks email addresses.
 * The facts come from the program README. The copy states the limits of the email check and the privacy duties of the buyer.
 */
class EmailFinderCatalog
{
    public static function program(): array
    {
        return [
            'slug' => 'email-finder-pro',
            'sort_order' => 6,
            'image_path' => 'images/software/email-finder-pro.svg',
            'name_en' => 'Email Finder Pro',
            'name_ar' => 'إيميل فايندر برو',
            'tagline_en' => 'Find the public business emails on company websites, then check every address before you send.',
            'tagline_ar' => 'اعثر على الإيميلات العامة في مواقع الشركات، وافحص كل عنوان قبل ما تبعت.',
            'description_en' => 'Paste websites, collect public emails, phones and social pages, and check addresses with clear verdicts.',
            'description_ar' => 'الصق مواقع، واجمع الإيميلات والتليفونات وصفحات التواصل العامة، وافحص العناوين بحكم واضح.',
            'price' => 1500,
            'currency' => 'EGP',
            'theme' => [
                'colors' => ['accent' => '#7c3aed', 'ink' => '#1a0b33'],
                'sections' => ['problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery'],
            ],
            'landing' => ['ar' => self::arabic(), 'en' => self::english()],
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'إيميلات الشركات',
            'headline' => 'الصق مواقع الشركات، وخد الإيميلات العامة، وافحصها قبل ما تبعت.',
            'subheadline' => 'البرنامج بيقرا الصفحة الرئيسية وصفحات الاتصال في كل موقع، ويطلّع الإيميلات والتليفونات وصفحات التواصل، وبعدين يفحص كل عنوان ويديك حكم وسبب.',
            'overview' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                ['title' => 'مناسب لمين؟', 'body' => 'اللي عنده قايمة شركات ومحتاج إيميلاتها العامة، واللي عنده قايمة إيميلات ومحتاج ينضفها قبل الحملة.'],
                ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 (64 بت) واتصال إنترنت. مفيش حسابات ولا مفاتيح API.'],
                ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، ولو فتحت صفحة الشراء من نافذة البرنامج بيتفعل جهازك فورا. وبتوصلك رسالة واتساب جاهزة برقم طلبك.'],
            ]],
            'problems' => ['kicker' => 'مشاكل الإيميلات', 'title' => 'ليه حملات الإيميل بتفشل؟', 'items' => [
                ['icon' => 'search', 'title' => 'إيميلات مش معاك', 'body' => 'عندك أسماء شركات ومواقع، لكن مفيش إيميل تتواصل عليه.'],
                ['icon' => 'mail', 'title' => 'عناوين غلط', 'body' => 'أخطاء كتابة زي gmial.com وإيميلات مؤقتة بتضيع الإرسال.'],
                ['icon' => 'shield', 'title' => 'سمعة المرسل', 'body' => 'كل إيميل راجع بيضر سمعة دومينك عند مزودي البريد.'],
            ]],
            'stat' => ['kicker' => 'فحص واضح', 'value' => '4', 'title' => 'أحكام واضحة لكل عنوان', 'body' => 'صالح، أو مشكوك فيه، أو غير صالح، أو غير معروف، ومعاه السبب بالعربي. فتعرف تبعت لمين وتراجع مين.'],
            'showcase' => ['kicker' => 'شاشات البرنامج', 'title' => 'من الموقع للقايمة النضيفة', 'items' => [
                ['title' => 'البحث عن الإيميلات', 'body' => 'الصق المواقع وحدد عدد الصفحات لكل موقع.'],
                ['title' => 'فحص الإيميلات', 'body' => 'الصق عناوين أو حمّل ملف، وشوف الحكم والسبب.'],
                ['title' => 'تصدير منظم', 'body' => 'ملفات منفصلة للصالح وغير الصالح وللمراجعة.'],
            ]],
            'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز لقايمة إيميلات سليمة', 'items' => [
                ['icon' => 'search', 'title' => 'زاحف مهذب', 'body' => 'نفس الموقع بس، بيحترم robots.txt، وبينتظر بين الطلبات، وبيعرّف عن نفسه بوضوح.'],
                ['icon' => 'mail', 'title' => 'إيميلات وتليفونات وتواصل', 'body' => 'بيلاقي الروابط والنصوص المموهة زي [at] و[dot] وإيميلات Cloudflare، والتليفونات بالأرقام العربية، وصفحات التواصل.'],
                ['icon' => 'check', 'title' => 'فحص العناوين', 'body' => 'شكل العنوان، وأخطاء مزودي البريد، والإيميلات المؤقتة، والإيميلات المشتركة، وسجلات MX.'],
                ['icon' => 'lock', 'title' => 'موافقة قبل البدء', 'body' => 'شاشة تأكيد المسؤولية بتظهر أول مرة، والبرنامج مش بيدخل حسابات ولا بيجمع من شبكات التواصل.'],
            ]],
            'video' => ['kicker' => 'شوف بنفسك', 'title' => 'جولة سريعة جوه البرنامج'],
            'steps' => ['kicker' => 'خطوات الاستخدام', 'title' => 'من أول فتح للقايمة النضيفة في 4 خطوات', 'items' => [
                ['title' => 'وافق على التنبيه', 'body' => 'اقرا شروط الاستخدام المسؤول وأكّد.'],
                ['title' => 'الصق المواقع', 'body' => 'موقع في كل سطر، وحدد عدد الصفحات.'],
                ['title' => 'افحص العناوين', 'body' => 'زرار واحد ينقل الإيميلات لشاشة الفحص.'],
                ['title' => 'صدّر', 'body' => 'CSV أو Excel، والصالح لوحده.'],
            ]],
            'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد البرنامج', 'before_title' => 'البحث اليدوي', 'after_title' => 'مع إيميل فايندر برو',
                'before' => [['b' => 'الشغل', 't' => 'فتح كل موقع وتدوّر على صفحة اتصل بنا.'], ['b' => 'الجودة', 't' => 'ترسل وتعرف الغلط بعد الرد الراجع.'], ['b' => 'الملف', 't' => 'نسخ ولصق في جدول.']],
                'after' => [['b' => 'الشغل', 't' => 'مواقع كتير في دفعة واحدة.'], ['b' => 'الجودة', 't' => 'حكم وسبب لكل عنوان قبل الإرسال.'], ['b' => 'الملف', 't' => 'ملفات جاهزة للصالح والمراجعة.']]],
            'metrics' => ['kicker' => 'ملخص', 'title' => 'البرنامج في أرقام', 'items' => [
                ['value' => '25', 'label' => 'أقصى عدد صفحات لكل موقع'], ['value' => '300+', 'label' => 'نطاق بريد مؤقت معروف'], ['value' => '4', 'label' => 'أحكام لكل عنوان']]],
            'gallery' => ['title' => 'صور من البرنامج'],
            'details' => ['title' => 'تفاصيل البرنامج', 'paragraphs' => [
                'إيميل فايندر برو برنامج ويندوز فيه شاشتين. الأولى بتقرا مواقع الشركات العامة وتطلّع الإيميلات والتليفونات وصفحات التواصل مع الصفحة اللي لقاها فيها. التانية بتفحص عناوين البريد وتقولك صالح ولا لأ وليه.',
                'فحص SMTP اختياري ومقفول افتراضيا، وهو بيسأل خادم البريد من غير ما يبعت أي رسالة. النتيجة مش مضمونة لأن خوادم كتير بتقبل كل العناوين أو بتحجب الفحص. فاعتبر الحكم مؤشر مش ضمان.',
            ], 'list_title' => 'اللي هتحصل عليه', 'list' => [
                'قراءة مواقع الشركات (نفس الموقع بس) وحد أقصى 25 صفحة لكل موقع', 'احترام robots.txt وانتظار عشوائي وزرار إيقاف',
                'إيميلات وتليفونات وصفحات تواصل مع الصفحة المصدر', 'تمييز الإيميلات المشتركة مثل info@ و sales@', 'فحص الشكل وأخطاء الكتابة والإيميلات المؤقتة وسجلات MX',
                'فحص SMTP اختياري ومقفول افتراضيا', 'تصدير CSV وExcel وملفات منفصلة للصالح وغير الصالح وللمراجعة', 'سجل عمليات وواجهة عربي وإنجليزي', 'تحديث تلقائي',
            ]],
            'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                ['q' => 'هل الإيميلات مضمونة؟', 'a' => 'البرنامج بيطلّع الإيميلات المنشورة علنا فقط. لو الموقع مش ناشر إيميل، مش هيلاقي حاجة. ومفيش ضمان لعدد النتايج.'],
                ['q' => 'هل فحص الإيميل مضمون 100%؟', 'a' => 'لا. فحص النطاق وسجلات MX موثوق نسبيا، لكن الخوادم اللي بتقبل كل العناوين بتخلي بعض الإيميلات تبان صالحة وهي مش كده. فحص SMTP كمان ممكن يتحجب، وكتير من شركات الإنترنت بتقفل المنفذ 25.'],
                ['q' => 'أبعت لكل الإيميلات اللي لقاها؟', 'a' => 'لأ. الإيميل العام للشركة مش معناه موافقة على الحملات. التزم بقوانين حماية البيانات ومكافحة الرسائل المزعجة (في مصر قانون حماية البيانات الشخصية)، وحط رابط إلغاء اشتراك واضح، وقف لما حد يطلب.'],
                ['q' => 'بعض المواقع بتمنع الاستخراج في شروطها؟', 'a' => 'فعلا. البرنامج بيحترم robots.txt، لكن شروط بعض المواقع قد تمنع الاستخراج حتى لو robots.txt بيسمح. راجع الشروط.'],
                ['q' => 'فحص SMTP ممكن يضرني؟', 'a' => 'ممكن عنوان IP بتاعك يتحط في قوايم الحظر لو كتّرت. عشان كده هو مقفول افتراضيا وبطيء عمدا.'],
                ['q' => 'بيشتغل على أنهي جهاز؟', 'a' => 'ويندوز 10 أو 11 (64 بت).'],
                ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
            ]],
            'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز تجمع وتنضّف قايمة إيميلاتك؟', 'body' => 'ادفع مرة واحدة في السنة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري إيميل فايندر برو'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'Company emails',
            'headline' => 'Paste company websites, get their public emails, and check them before you send.',
            'subheadline' => 'The program reads the home page and the contact pages of each site, lists the emails, phones and social pages it finds, then checks every address and gives a verdict and a reason.',
            'overview' => ['title' => 'Understand everything in one minute', 'items' => [
                ['title' => 'Who is it for?', 'body' => 'People who have a list of companies and need their public emails, and people who have a list of emails and need to clean it before a campaign.'],
                ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 (64-bit) computer and an internet connection. No accounts and no API keys.'],
                ['title' => 'What happens after payment?', 'body' => 'After payment your account and license are created automatically, and if you opened the buy page from the program window your device is activated right away. You also get a ready WhatsApp message with your order number.'],
            ]],
            'problems' => ['kicker' => 'Problems with emails', 'title' => 'Why do email campaigns fail?', 'items' => [
                ['icon' => 'search', 'title' => 'Emails you do not have', 'body' => 'You have company names and websites, but no email to contact.'],
                ['icon' => 'mail', 'title' => 'Wrong addresses', 'body' => 'Typos like gmial.com and disposable emails ruin a send.'],
                ['icon' => 'shield', 'title' => 'Sender reputation', 'body' => 'Every bounced email hurts your domain with mail providers.'],
            ]],
            'stat' => ['kicker' => 'A clear check', 'value' => '4', 'title' => 'Clear verdicts for every address', 'body' => 'Valid, risky, invalid or unknown, with the reason beside it. So you know who to send to and who to review.'],
            'showcase' => ['kicker' => 'Program screens', 'title' => 'From a website to a clean list', 'items' => [
                ['title' => 'Find emails', 'body' => 'Paste websites and set the pages per site.'],
                ['title' => 'Check emails', 'body' => 'Paste addresses or load a file, and see the verdict and the reason.'],
                ['title' => 'Organized export', 'body' => 'Separate files for good, bad and review.'],
            ]],
            'pillars' => ['kicker' => 'What the program can do', 'title' => 'Four pillars of a healthy email list', 'items' => [
                ['icon' => 'search', 'title' => 'A polite crawler', 'body' => 'Same site only, obeys robots.txt, waits between requests, and says clearly who it is.'],
                ['icon' => 'mail', 'title' => 'Emails, phones and social pages', 'body' => 'Finds links and disguised text like [at] and [dot], Cloudflare-hidden emails, phones with Arabic digits, and social pages.'],
                ['icon' => 'check', 'title' => 'Address checks', 'body' => 'Address format, provider typos, disposable emails, shared role emails, and MX records.'],
                ['icon' => 'lock', 'title' => 'Consent first', 'body' => 'A responsibility screen shows on first use, and the program does not log in to accounts or collect from social networks.'],
            ]],
            'video' => ['kicker' => 'See it yourself', 'title' => 'A quick tour inside the program'],
            'steps' => ['kicker' => 'How to use it', 'title' => 'From first launch to a clean list in 4 steps', 'items' => [
                ['title' => 'Accept the notice', 'body' => 'Read the responsible-use terms and confirm.'],
                ['title' => 'Paste the websites', 'body' => 'One site per line, and set the number of pages.'],
                ['title' => 'Check the addresses', 'body' => 'One button moves the emails to the check screen.'],
                ['title' => 'Export', 'body' => 'CSV or Excel, and the good ones on their own.'],
            ]],
            'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the program', 'before_title' => 'Searching by hand', 'after_title' => 'With Email Finder Pro',
                'before' => [['b' => 'Work', 't' => 'Open each site and look for the contact page.'], ['b' => 'Quality', 't' => 'You send, and learn of mistakes after the bounce.'], ['b' => 'The file', 't' => 'Copy and paste into a table.']],
                'after' => [['b' => 'Work', 't' => 'Many websites in one batch.'], ['b' => 'Quality', 't' => 'A verdict and a reason for each address before sending.'], ['b' => 'The file', 't' => 'Ready files for good and for review.']]],
            'metrics' => ['kicker' => 'Summary', 'title' => 'The program in numbers', 'items' => [
                ['value' => '25', 'label' => 'Most pages per site'], ['value' => '300+', 'label' => 'Known disposable domains'], ['value' => '4', 'label' => 'Verdicts per address']]],
            'gallery' => ['title' => 'Pictures of the program'],
            'details' => ['title' => 'Program details', 'paragraphs' => [
                'Email Finder Pro is a Windows program with two screens. The first reads public company websites and lists the emails, phones and social pages it finds, with the page where it found each one. The second checks email addresses and tells you whether each is good and why.',
                'The SMTP check is optional and off by default. It asks the mail server without sending any message. The result is not guaranteed, because many servers accept every address or block the check. Treat a verdict as a signal, not a promise.',
            ], 'list_title' => 'What you get', 'list' => [
                'Reads company sites (same site only), up to 25 pages per site', 'Obeys robots.txt, random waits and a Stop button',
                'Emails, phones and social pages with the source page', 'Marks shared emails such as info@ and sales@', 'Checks format, typos, disposable emails and MX records',
                'An optional SMTP check, off by default', 'CSV and Excel export, with separate files for good, bad and review', 'A history and an Arabic and English interface', 'Automatic updates',
            ]],
            'faq' => ['title' => 'Common questions', 'items' => [
                ['q' => 'Are the emails guaranteed?', 'a' => 'The program only lists emails that are published openly. If a site does not publish an email, it finds nothing. The number of results is not guaranteed.'],
                ['q' => 'Is the email check 100% accurate?', 'a' => 'No. The domain and MX checks are fairly reliable, but servers that accept every address can make an address look valid when it is not. The SMTP check can be blocked too, and many internet providers close port 25.'],
                ['q' => 'Can I send to every email it finds?', 'a' => 'No. A public company email does not mean consent to campaigns. Follow data protection and anti-spam laws (in Egypt, the Personal Data Protection Law), add a clear unsubscribe link, and stop when someone asks.'],
                ['q' => 'Some sites forbid scraping in their terms.', 'a' => 'True. The program obeys robots.txt, but the terms of some sites may forbid extraction even where robots.txt allows it. Read the terms.'],
                ['q' => 'Can the SMTP check hurt me?', 'a' => 'Your IP address can land on blocklists if you overuse it. That is why it is off by default and slow on purpose.'],
                ['q' => 'Which computers does it run on?', 'a' => 'Windows 10 or 11 (64-bit).'],
                ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
            ]],
            'cta' => ['kicker' => 'Start today', 'title' => 'Ready to collect and clean your email list?', 'body' => 'Pay once a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Email Finder Pro'],
        ];
    }
}
