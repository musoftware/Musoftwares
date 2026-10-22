<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Inbox Sender Pro: a desktop program that sends rotated messages and pictures to the inbox of a Facebook page.
 * The facts come from the program README. It was not tested against a live Facebook account, so the copy
 * promises nothing about Facebook itself and says what the buyer must check first.
 */
class InboxSenderCatalog
{
    public static function program(): array
    {
        return [
            'slug' => 'inbox-sender-pro',
            'sort_order' => 4,
            'image_path' => 'images/software/inbox-sender-pro.svg',
            'name_en' => 'Inbox Sender Pro',
            'name_ar' => 'إنبوكس سيندر برو',
            'tagline_en' => 'Send rotated messages and pictures to the inbox of your Facebook page, with safe random delays.',
            'tagline_ar' => 'ابعت رسايل وصور متنوعة لمحادثات صفحة فيسبوك بتاعتك بفواصل زمنية عشوائية آمنة.',
            'description_en' => 'Load the inbox of a Facebook page, filter conversations, and send rotated messages and pictures.',
            'description_ar' => 'حمّل محادثات صفحة فيسبوك، وفلترها، وابعت رسايل وصور متنوعة.',
            'price' => 2000,
            'currency' => 'EGP',
            'theme' => [
                'colors' => ['accent' => '#e11d48', 'ink' => '#1c0a10'],
                'sections' => ['problems', 'stat', 'showcase', 'pillars', 'video', 'steps', 'before_after', 'metrics', 'gallery'],
            ],
            'landing' => ['ar' => self::arabic(), 'en' => self::english()],
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'رسائل صفحات فيسبوك',
            'headline' => 'كلّم محادثات صفحتك كلها من برنامج واحد، من غير ما تبعت نفس الرسالة مرتين.',
            'subheadline' => 'البرنامج بيقرا محادثات صفحتك على فيسبوك، وبيبعت رسايل وصور بالتبادل بفواصل عشوائية، وبيسجل كل محادثة اتبعتلها عشان متتكررش.',
            'overview' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                ['title' => 'مناسب لمين؟', 'body' => 'أصحاب صفحات فيسبوك اللي عندهم محادثات كتير ومحتاجين يردوا عليها برسالة أو صورة بشكل منظم.'],
                ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11، ومتصفح Chrome أو Edge، وصفحة فيسبوك بتديرها، وواجهة فيسبوك باللغة الإنجليزية.'],
                ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، ولو فتحت صفحة الشراء من نافذة البرنامج بيتفعل جهازك فورا. وبتوصلك رسالة واتساب جاهزة برقم طلبك.'],
            ]],
            'problems' => ['kicker' => 'مشاكل الرد على المحادثات', 'title' => 'ليه الرد على المحادثات بيتعب؟', 'items' => [
                ['icon' => 'clock', 'title' => 'محادثات بالمئات', 'body' => 'تفتح كل محادثة وتكتب وترفع الصورة بإيدك.'],
                ['icon' => 'message', 'title' => 'رسالة واحدة تتكرر', 'body' => 'نفس النص لكل الناس بيبان آلي.'],
                ['icon' => 'check', 'title' => 'تكرار الإرسال', 'body' => 'مفيش تسجيل واضح لمين استلم ومين لأ.'],
            ]],
            'stat' => ['kicker' => 'إرسال منظم', 'value' => '1', 'title' => 'رسالة واحدة لكل محادثة', 'body' => 'كل محادثة بتتاخد من الطابور مرة واحدة وبتتعلم قبل الإرسال، فمستحيل تتبعتلها مرتين بالغلط.'],
            'showcase' => ['kicker' => 'شاشات البرنامج', 'title' => 'من تحميل المحادثات للإرسال', 'items' => [
                ['title' => 'حسابات وبروفايلات', 'body' => 'كل صفحة ليها بروفايل Chrome لوحدها بيحفظ تسجيل الدخول.'],
                ['title' => 'تحميل الصندوق', 'body' => 'بيقرا المحادثات لحد حد انت بتحدده، ومعاه فلترة بكلمات.'],
                ['title' => 'إرسال ومتابعة', 'body' => 'إرسال الكل أو المحدد أو إيقاف، وسجل لكل خطوة.'],
            ]],
            'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'أربع ركائز للإرسال المنظم', 'items' => [
                ['icon' => 'users', 'title' => 'حسابات متعددة', 'body' => 'أضف صفحات كتير، وافتح واقفل كل واحدة.'],
                ['icon' => 'search', 'title' => 'تحميل وفلترة', 'body' => 'حمّل المحادثات، وخلّي بس اللي فيها كلمة تهمك.'],
                ['icon' => 'refresh', 'title' => 'رسائل وصور بالتبادل', 'body' => 'رسايل كتير وصور كتير بتتستخدم بالدور، و$$NAME$$ بيتحول لاسم العميل.'],
                ['icon' => 'shield', 'title' => 'إرسال بحذر', 'body' => 'فاصل عشوائي بين الرسايل، وتأكيد إن النص اتكتب صح وإن مربع الكتابة اتفضى.'],
            ]],
            'video' => ['kicker' => 'شوف بنفسك', 'title' => 'جولة سريعة جوه البرنامج'],
            'steps' => ['kicker' => 'خطوات الاستخدام', 'title' => 'من أول فتح للإرسال في 4 خطوات', 'items' => [
                ['title' => 'أضف الصفحة', 'body' => 'اسم الصفحة والـ ID، وسجل دخول فيسبوك من نافذة Chrome.'],
                ['title' => 'حمّل المحادثات', 'body' => 'حدد العدد وفلتر بالكلمات لو عايز.'],
                ['title' => 'جهز الرسايل', 'body' => 'ضيف رسايل وصور، من ملف txt أو csv لو كتير.'],
                ['title' => 'ابدأ الإرسال', 'body' => 'تابع السجل، وأوقف وقت ما تحب.'],
            ]],
            'before_after' => ['kicker' => 'الفرق العملي', 'title' => 'قبل وبعد البرنامج', 'before_title' => 'الرد اليدوي', 'after_title' => 'مع إنبوكس سيندر برو',
                'before' => [['b' => 'الشغل', 't' => 'فتح كل محادثة وكتابة الرسالة.'], ['b' => 'التنويع', 't' => 'نص واحد لكل الناس.'], ['b' => 'التتبع', 't' => 'مفيش سجل بمين استلم.']],
                'after' => [['b' => 'الشغل', 't' => 'تحميل المحادثات وإرسال منظم.'], ['b' => 'التنويع', 't' => 'رسايل وصور بالتبادل.'], ['b' => 'التتبع', 't' => 'كل محادثة مرة واحدة وسجل كامل.']]],
            'metrics' => ['kicker' => 'ملخص', 'title' => 'البرنامج في أرقام', 'items' => [
                ['value' => '1', 'label' => 'رسالة لكل محادثة'], ['value' => '73', 'label' => 'اختبار آلي في البرنامج'], ['value' => 'Chrome', 'label' => 'أو Edge لتشغيل فيسبوك']]],
            'gallery' => ['title' => 'صور من البرنامج'],
            'details' => ['title' => 'تفاصيل البرنامج', 'paragraphs' => [
                'إنبوكس سيندر برو برنامج ويندوز بيقرا محادثات صفحة فيسبوك من Meta Business Suite وبيبعتلها رسايل وصور. مش بيستخدم Facebook API، بيشغّل نافذة Chrome أو Edge حقيقية فاضلة مسجلة دخول فيسبوك، زي ما الشخص بيعمل.',
                'كل صفحة ليها بروفايل منفصل. بتحمل المحادثات، وتفلترها بكلمات تسمح أو تمنع، وتجهز رسايل وصور كتير بتتبعت بالدور مع اسم العميل، والبرنامج بيستنى وقت عشوائي بين كل رسالة.',
            ], 'list_title' => 'اللي هتحصل عليه', 'list' => [
                'حسابات وبروفايلات منفصلة لكل صفحة', 'تحميل المحادثات لحد حد تحدده', 'كلمات تسمح وكلمات تمنع للفلترة',
                'تحميل بالـ IDs ومراقبة المحادثات الجديدة وإرسالها لعنوان ويب', 'رسايل وصور بالتبادل مع $$NAME$$',
                'فاصل عشوائي وتأكيد كتابة النص وتفريغ مربع الكتابة', 'إرسال الكل أو المحدد وإيقاف، والفاشل يفضل في القايمة',
                'حفظ وتحميل القوايم وتصدير الـ IDs', 'سجل أنشطة وتحديث تلقائي',
            ]],
            'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                ['q' => 'محتاج Facebook API؟', 'a' => 'لا. البرنامج بيشغّل Chrome أو Edge حقيقي مسجل دخول فيسبوك.'],
                ['q' => 'واجهة فيسبوك بأي لغة؟', 'a' => 'لازم تكون إنجليزي، لأن البرنامج بيعتمد على نصوص الواجهة الإنجليزية.'],
                ['q' => 'هل مضمون يشتغل على فيسبوك؟', 'a' => 'فيسبوك بيغير صفحاته، والبرنامج بيعتمد على عناصر الصفحة. جرّبه الأول بحد صغير وكام محادثة تجريبية على صفحتك. لو فيسبوك غيّر حاجة بنحدّث البرنامج والتحديث بيوصلك تلقائي.'],
                ['q' => 'ممكن صفحتي تتقيد؟', 'a' => 'أي إرسال جماعي على فيسبوك فيه خطر تقييد. البرنامج بيستخدم فواصل عشوائية، لكن مفيش ضمان. التزم بسياسات فيسبوك وابعت لمحادثات مفتوحة معاك فعلا.'],
                ['q' => 'البرنامج بيشتغل على أنهي جهاز؟', 'a' => 'ويندوز 10 أو 11 (64 بت) ومعاه Chrome أو Edge.'],
                ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
                ['q' => 'لو احتجت مساعدة؟', 'a' => 'كلمنا على الواتساب برقم طلبك.'],
            ]],
            'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز ترد على محادثات صفحتك بنظام؟', 'body' => 'ادفع مرة واحدة في السنة واستلم البرنامج. بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري إنبوكس سيندر برو'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'Facebook page messages',
            'headline' => 'Message every conversation of your page from one program, without sending the same message twice.',
            'subheadline' => 'The program reads the conversations of your Facebook page, sends rotated messages and pictures with random delays, and records every conversation it sent to, so none repeats.',
            'overview' => ['title' => 'Understand everything in one minute', 'items' => [
                ['title' => 'Who is it for?', 'body' => 'Owners of Facebook pages with many conversations who need to answer them with a message or picture in an organized way.'],
                ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 computer, the Chrome or Edge browser, a Facebook page you manage, and the Facebook interface in English.'],
                ['title' => 'What happens after payment?', 'body' => 'After payment your account and license are created automatically, and if you opened the buy page from the program window your device is activated right away. You also get a ready WhatsApp message with your order number.'],
            ]],
            'problems' => ['kicker' => 'Problems of answering conversations', 'title' => 'Why is answering conversations so tiring?', 'items' => [
                ['icon' => 'clock', 'title' => 'Hundreds of conversations', 'body' => 'You open each one, type and attach the picture by hand.'],
                ['icon' => 'message', 'title' => 'One message repeated', 'body' => 'The same text for everyone looks automatic.'],
                ['icon' => 'check', 'title' => 'Repeated sending', 'body' => 'No clear record of who received it and who did not.'],
            ]],
            'stat' => ['kicker' => 'Organized sending', 'value' => '1', 'title' => 'One message per conversation', 'body' => 'Every conversation is taken from the queue once and marked before it is sent, so it can never be sent twice by mistake.'],
            'showcase' => ['kicker' => 'Program screens', 'title' => 'From loading conversations to sending', 'items' => [
                ['title' => 'Accounts and profiles', 'body' => 'Each page has its own Chrome profile that keeps the login.'],
                ['title' => 'Load the inbox', 'body' => 'Reads conversations up to a limit you set, with word filters.'],
                ['title' => 'Send and follow', 'body' => 'Send all or selected, or stop, with a log for every step.'],
            ]],
            'pillars' => ['kicker' => 'What the program can do', 'title' => 'Four pillars of organized sending', 'items' => [
                ['icon' => 'users', 'title' => 'Several accounts', 'body' => 'Add many pages, and open and close each one.'],
                ['icon' => 'search', 'title' => 'Load and filter', 'body' => 'Load conversations and keep only those with a word you care about.'],
                ['icon' => 'refresh', 'title' => 'Rotated messages and pictures', 'body' => 'Many messages and pictures are used in turn, and $$NAME$$ becomes the customer name.'],
                ['icon' => 'shield', 'title' => 'Careful sending', 'body' => 'A random delay between messages, and a check that the text was typed right and the chat box emptied.'],
            ]],
            'video' => ['kicker' => 'See it yourself', 'title' => 'A quick tour inside the program'],
            'steps' => ['kicker' => 'How to use it', 'title' => 'From first launch to sending in 4 steps', 'items' => [
                ['title' => 'Add the page', 'body' => 'Page name and ID, and log in to Facebook in the Chrome window.'],
                ['title' => 'Load conversations', 'body' => 'Set the number and filter by words if you like.'],
                ['title' => 'Prepare the messages', 'body' => 'Add messages and pictures, from a txt or csv file if many.'],
                ['title' => 'Start sending', 'body' => 'Follow the log, and stop any time.'],
            ]],
            'before_after' => ['kicker' => 'The practical difference', 'title' => 'Before and after the program', 'before_title' => 'Answering by hand', 'after_title' => 'With Inbox Sender Pro',
                'before' => [['b' => 'Work', 't' => 'Open each conversation and type the message.'], ['b' => 'Variety', 't' => 'One text for everyone.'], ['b' => 'Tracking', 't' => 'No record of who received it.']],
                'after' => [['b' => 'Work', 't' => 'Load conversations and send in order.'], ['b' => 'Variety', 't' => 'Rotated messages and pictures.'], ['b' => 'Tracking', 't' => 'Each conversation once and a full log.']]],
            'metrics' => ['kicker' => 'Summary', 'title' => 'The program in numbers', 'items' => [
                ['value' => '1', 'label' => 'Message per conversation'], ['value' => '73', 'label' => 'Automated tests in the program'], ['value' => 'Chrome', 'label' => 'Or Edge to run Facebook']]],
            'gallery' => ['title' => 'Pictures of the program'],
            'details' => ['title' => 'Program details', 'paragraphs' => [
                'Inbox Sender Pro is a Windows program that reads the conversations of a Facebook page from Meta Business Suite and sends them messages and pictures. It does not use the Facebook API. It drives a real Chrome or Edge window that stays logged in to Facebook, the way a person would.',
                'Each page has its own profile. You load the conversations, filter them with allow and disallow words, prepare many messages and pictures that are sent in turn with the customer name, and the program waits a random time between messages.',
            ], 'list_title' => 'What you get', 'list' => [
                'Separate accounts and profiles for each page', 'Load conversations up to a limit you set', 'Allow words and disallow words for filtering',
                'Load by IDs, and watch new conversations and post them to a web address', 'Rotated messages and pictures with $$NAME$$',
                'A random delay, a check of the typed text and of the emptied chat box', 'Send all or selected, and stop; failed ones stay in the list',
                'Save and load lists, and export the IDs', 'An activity log and automatic updates',
            ]],
            'faq' => ['title' => 'Common questions', 'items' => [
                ['q' => 'Do I need the Facebook API?', 'a' => 'No. The program drives a real Chrome or Edge window that is logged in to Facebook.'],
                ['q' => 'Which language must Facebook use?', 'a' => 'English, because the program relies on the English interface texts.'],
                ['q' => 'Is it guaranteed to work with Facebook?', 'a' => 'Facebook changes its pages, and the program relies on elements of the page. Try it first with a small limit and a few test conversations on your page. If Facebook changes something we update the program, and the update reaches you automatically.'],
                ['q' => 'Can my page get restricted?', 'a' => 'Any bulk sending on Facebook risks a restriction. The program uses random delays, but there is no guarantee. Follow Facebook policies and message conversations that are really open with you.'],
                ['q' => 'Which computers does it run on?', 'a' => 'Windows 10 or 11 (64-bit) with Chrome or Edge.'],
                ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
                ['q' => 'What if I need help?', 'a' => 'Message us on WhatsApp with your order number.'],
            ]],
            'cta' => ['kicker' => 'Start today', 'title' => 'Ready to answer your page conversations in an organized way?', 'body' => 'Pay once a year and get the program. After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Inbox Sender Pro'],
        ];
    }
}
