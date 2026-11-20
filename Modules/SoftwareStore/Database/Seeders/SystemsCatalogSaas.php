<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * The six multi-company systems (one installation serves many companies, each sees only its own data):
 * travel agency, CRM and accounting, private school, contracting, law office and fleet.
 * They share the same hosting needs, online payments (Paymob, Fawry, InstaPay) and WhatsApp Business module,
 * so those answers live here once. Each system brings 4 screenshots taken from a local run with its demo data.
 */
class SystemsCatalogSaas
{
    public const SCREENS = 4;

    /** @return array<int, array<string, mixed>> */
    public static function systems(): array
    {
        return [
            SystemsCatalogTravel::system(), SystemsCatalogCrmAccounting::system(), SystemsCatalogSchool::system(),
            SystemsCatalogContracting::system(), SystemsCatalogLaw::system(), SystemsCatalogFleet::system(),
        ];
    }

    /**
     * Hosting, what is included and the online payment answer, with what the customer can pay in this system.
     *
     * @return array<string, mixed>
     */
    public static function extras(string $lang, string $payable): array
    {
        return [
            'ar' => [
                'need_body' => 'استضافة بتدعم PHP 8.3 أو أحدث وقاعدة بيانات SQLite أو MySQL، ومهمة cron كل دقيقة (للتذكيرات ومتابعة المدفوعات). بيتركب على استضافة عادية (cPanel) من غير Redis، ونسخة واحدة تكفي أكتر من شركة.',
                'included' => ['عربي وإنجليزي مع دعم الاتجاه من اليمين', 'دفع أونلاين بحساباتك انت على Paymob وفوري وإنستاباي', 'واتساب Business: الرسايل والردود في صندوق واحد، أو رسالة جاهزة بتفتح واتساب من غير حساب'],
                'payments_faq' => ['q' => 'بيدعم دفع أونلاين جوه النظام؟', 'a' => "أيوه. صاحب الشركة بيربط حساباته هو على Paymob (كروت ومحافظ) وفوري، أو بيكتب عنوان إنستاباي، والعميل بيدفع {$payable} من رابط من غير ما يعمل حساب، والفلوس بتتسجل إيصال لوحدها. الربط مكتوب على توثيق البوابات ومتجرب على ردود تجريبية بس، ومتجربش على حساب حقيقي، فجرّبه بمبلغ صغير الأول."],
            ],
            'en' => [
                'need_body' => 'Hosting with PHP 8.3 or newer and an SQLite or MySQL database, plus a cron job every minute (for reminders and payment checks). It installs on normal shared hosting (cPanel) with no Redis, and one installation can serve several companies.',
                'included' => ['Arabic and English with right-to-left support', 'Online payments through your own Paymob, Fawry and InstaPay accounts', 'WhatsApp Business: messages and replies in one inbox, or a ready WhatsApp message without an account'],
                'payments_faq' => ['q' => 'Does it take online payments inside the system?', 'a' => "Yes. The owner connects the company's own Paymob (cards and wallets) and Fawry accounts, or types an InstaPay address, and the customer pays {$payable} from a link without signing up. The money is recorded as a receipt by itself. The connection follows the gateways' documentation and is tested against simulated answers only, not against a real account, so try it with a small amount first."],
            ],
        ][$lang];
    }
}
