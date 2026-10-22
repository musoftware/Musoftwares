<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Telegram Sender Pro: a desktop program that sends rotated messages to Telegram users the owner is allowed to contact.
 * It was NOT tried against a real Telegram account, so the product stays hidden (is_active false) until someone does.
 * Do not turn it on in the admin page before that test.
 */
class TelegramSenderCatalog
{
    public static function program(): array
    {
        return [
            'slug' => 'telegram-sender-pro',
            'sort_order' => 7,
            'image_path' => 'images/software/telegram-sender-pro.svg',
            'name_en' => 'Telegram Sender Pro',
            'name_ar' => 'تليجرام سيندر برو',
            'tagline_en' => 'Send rotated messages to Telegram users who agreed to hear from you, with a daily cap and random delays.',
            'tagline_ar' => 'ابعت رسايل متنوعة لمستخدمي تليجرام اللي وافقوا يسمعوا منك، بحد يومي وفواصل عشوائية.',
            'description_en' => 'Manage Telegram accounts, list the members of your own groups, and send rotated messages with a daily cap.',
            'description_ar' => 'أدر حسابات تليجرام، واعرض أعضاء جروباتك، وابعت رسايل متنوعة بحد يومي.',
            'price' => 1000,
            'currency' => 'EGP',
            'theme' => [
                'colors' => ['accent' => '#0284c7', 'ink' => '#06202f'],
                'sections' => ['problems', 'stat', 'pillars', 'steps', 'gallery'],
            ],
            'landing' => ['ar' => self::arabic(), 'en' => self::english()],
        ];
    }

    private static function arabic(): array
    {
        return [
            'kicker' => 'رسائل تليجرام',
            'headline' => 'ابعت لجمهورك على تليجرام بنظام، وبحد يومي يحميك.',
            'subheadline' => 'البرنامج بيدير حسابات تليجرام، وبيعرض أعضاء جروباتك، وبيبعت رسايل متنوعة بفواصل عشوائية وحد يومي.',
            'overview' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                ['title' => 'مناسب لمين؟', 'body' => 'اللي عنده جمهور على تليجرام وافق يستلم منه رسايل.'],
                ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 (64 بت) وحساب تليجرام.'],
                ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، وبتوصلك رسالة واتساب جاهزة برقم طلبك.'],
            ]],
            'problems' => ['kicker' => 'مشاكل الإرسال', 'title' => 'ليه الإرسال على تليجرام بيتعب؟', 'items' => [
                ['icon' => 'clock', 'title' => 'رسايل يدوية', 'body' => 'تكتب لكل شخص بإيدك.'],
                ['icon' => 'message', 'title' => 'نص واحد', 'body' => 'نفس الرسالة للكل بتبان آلية.'],
                ['icon' => 'shield', 'title' => 'خطر الحظر', 'body' => 'الإرسال الكتير بيعرّض الحساب للتقييد.'],
            ]],
            'stat' => ['kicker' => 'حد يومي', 'value' => '100', 'title' => 'أقصى حد يومي للحساب', 'body' => 'الافتراضي 40 رسالة في اليوم، وأقصى حد 100، ومفيش انتظار أقل من 5 ثواني بين رسالتين.'],
            'pillars' => ['kicker' => 'قدرات البرنامج', 'title' => 'ركائز الإرسال المنظم', 'items' => [
                ['icon' => 'users', 'title' => 'حسابات وجروبات', 'body' => 'تسجيل بالتليفون والكود، وعرض أعضاء الجروبات.'],
                ['icon' => 'refresh', 'title' => 'رسايل بالتبادل', 'body' => 'رسايل متنوعة واسم العميل، وصورة اختيارية.'],
                ['icon' => 'shield', 'title' => 'وقف عند التحذير', 'body' => 'لو تليجرام حذّر الحساب البرنامج بيقف فورا.'],
                ['icon' => 'lock', 'title' => 'موافقة قبل كل حملة', 'body' => 'شاشة تأكيد المسؤولية، وكلمات إيقاف عربي وإنجليزي.'],
            ]],
            'steps' => ['kicker' => 'خطوات الاستخدام', 'title' => 'من أول فتح للإرسال في 4 خطوات', 'items' => [
                ['title' => 'أضف الحساب', 'body' => 'رقم التليفون وكود تليجرام.'],
                ['title' => 'اختار الجروب', 'body' => 'اعرض أعضاءه وصدّرهم.'],
                ['title' => 'جهز الرسايل', 'body' => 'رسايل وصورة اختيارية.'],
                ['title' => 'ابدأ', 'body' => 'تابع السجل وأوقف وقت ما تحب.'],
            ]],
            'gallery' => ['title' => 'صور من البرنامج'],
            'details' => ['title' => 'تفاصيل البرنامج', 'paragraphs' => [
                'تليجرام سيندر برو برنامج ويندوز بيستخدم حساب تليجرام بتاعك. مفيش ميزة لإضافة أعضاء لجروب.',
            ], 'list_title' => 'اللي هتحصل عليه', 'list' => [
                'حسابات مشفرة', 'عرض أعضاء الجروبات وتصدير CSV', 'رسايل بالتبادل مع الاسم وصورة اختيارية', 'حد يومي وفاصل عشوائي', 'سجل وحملات قابلة للاستكمال',
            ]],
            'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                ['q' => 'ممكن حسابي يتحظر؟', 'a' => 'تليجرام ممكن يقيّد أي حساب بيراسل ناس مكلموهوش الأول، حتى مع الحدود اليومية. لا يوجد ضمان.'],
                ['q' => 'بيشتغل على أنهي جهاز؟', 'a' => 'ويندوز 10 أو 11 (64 بت).'],
            ]],
            'cta' => ['kicker' => 'ابدأ النهارده', 'title' => 'جاهز؟', 'body' => 'بعد الدفع هنكلمك على الواتساب نفعّل معاك النسخة.', 'button' => 'اشتري تليجرام سيندر برو'],
        ];
    }

    private static function english(): array
    {
        return [
            'kicker' => 'Telegram messages',
            'headline' => 'Message your Telegram audience in an organized way, with a daily cap that protects you.',
            'subheadline' => 'The program manages Telegram accounts, lists the members of your groups, and sends rotated messages with random delays and a daily cap.',
            'overview' => ['title' => 'Understand everything in one minute', 'items' => [
                ['title' => 'Who is it for?', 'body' => 'People with a Telegram audience that agreed to receive their messages.'],
                ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 (64-bit) computer and a Telegram account.'],
                ['title' => 'What happens after payment?', 'body' => 'After payment your account and license are created automatically, and you get a ready WhatsApp message with your order number.'],
            ]],
            'problems' => ['kicker' => 'Problems of sending', 'title' => 'Why is sending on Telegram tiring?', 'items' => [
                ['icon' => 'clock', 'title' => 'Messages by hand', 'body' => 'You write to each person yourself.'],
                ['icon' => 'message', 'title' => 'One text', 'body' => 'The same message for everyone looks automatic.'],
                ['icon' => 'shield', 'title' => 'Risk of a ban', 'body' => 'Heavy sending exposes the account to limits.'],
            ]],
            'stat' => ['kicker' => 'A daily cap', 'value' => '100', 'title' => 'Highest daily cap per account', 'body' => 'The default is 40 messages a day, the highest is 100, and the wait between two messages is never below 5 seconds.'],
            'pillars' => ['kicker' => 'What the program can do', 'title' => 'Pillars of organized sending', 'items' => [
                ['icon' => 'users', 'title' => 'Accounts and groups', 'body' => 'Sign in with phone and code, and list group members.'],
                ['icon' => 'refresh', 'title' => 'Rotated messages', 'body' => 'Varied messages with the customer name, and an optional picture.'],
                ['icon' => 'shield', 'title' => 'Stops on warnings', 'body' => 'If Telegram warns the account, the program stops at once.'],
                ['icon' => 'lock', 'title' => 'Consent before each campaign', 'body' => 'A responsibility screen, and stop words in Arabic and English.'],
            ]],
            'steps' => ['kicker' => 'How to use it', 'title' => 'From first launch to sending in 4 steps', 'items' => [
                ['title' => 'Add the account', 'body' => 'Phone number and the Telegram code.'],
                ['title' => 'Pick the group', 'body' => 'List its members and export them.'],
                ['title' => 'Prepare the messages', 'body' => 'Messages and an optional picture.'],
                ['title' => 'Start', 'body' => 'Follow the log and stop any time.'],
            ]],
            'gallery' => ['title' => 'Pictures of the program'],
            'details' => ['title' => 'Program details', 'paragraphs' => [
                'Telegram Sender Pro is a Windows program that uses your own Telegram account. It has no feature to add members to a group.',
            ], 'list_title' => 'What you get', 'list' => [
                'Encrypted accounts', 'Group members list and CSV export', 'Rotated messages with the name and an optional picture', 'A daily cap and a random delay', 'A log and campaigns you can continue',
            ]],
            'faq' => ['title' => 'Common questions', 'items' => [
                ['q' => 'Can my account be banned?', 'a' => 'Telegram can limit any account that messages people who did not write first, even within the daily caps. There is no guarantee.'],
                ['q' => 'Which computers does it run on?', 'a' => 'Windows 10 or 11 (64-bit).'],
            ]],
            'cta' => ['kicker' => 'Start today', 'title' => 'Ready?', 'body' => 'After payment we message you on WhatsApp to activate your copy.', 'button' => 'Buy Telegram Sender Pro'],
        ];
    }
}
