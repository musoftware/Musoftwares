<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Shared texts for the connections the ready-made systems gained after their first copy: online payments through the
 * owner's own Paymob, Fawry and InstaPay accounts, WhatsApp Business (Cloud API), the ETA e-receipt and the online-store sync.
 * Each system says in its own copy what it actually sends or takes; the honest limits live here once:
 * the gateways, Meta and ETA were reached only through simulated answers in the tests.
 */
class SystemsCatalogOnline
{
    private const TEXTS = [
        'ar' => [
            'languages' => 'عربي وإنجليزي مع دعم الاتجاه من اليمين',
            'payments' => 'دفع أونلاين بحساباتك انت على Paymob وفوري وإنستاباي',
            'whatsapp' => 'رسايل واتساب Business بحسابك انت، أو رسالة جاهزة بتفتح واتساب من غير حساب',
            'eta' => 'إيصال إلكتروني لمصلحة الضرائب (ETA) بكود QR على الإيصال المطبوع',
            'store' => 'ربط بمتجرك على Shopify أو WooCommerce أو سلة',
            'need' => 'استضافة بتدعم PHP 8.3 أو أحدث وقاعدة بيانات SQLite أو MySQL، ومهمة cron كل دقيقة (%s). بيتركب على استضافة عادية (cPanel) من غير Redis.',
            'payments_q' => 'بيدعم دفع أونلاين جوه النظام؟',
            'payments_a' => 'أيوه. صاحب الشغل بيربط حساباته هو على Paymob (كروت ومحافظ) وفوري، أو بيكتب عنوان إنستاباي، والعميل بيدفع %s من رابط من غير ما يعمل حساب، والمبلغ بيتسجل في النظام لوحده زي الدفع في المكان. الربط مكتوب على توثيق البوابات ومتجرب على ردود تجريبية بس، ومتجربش على حساب حقيقي، فجرّبه بمبلغ صغير الأول. من غير الحسابات دي الدفع في المكان شغال زي ما هو.',
            'eta_q' => 'الإيصال الإلكتروني متجرب على مصلحة الضرائب؟',
            'eta_a' => 'لا. الربط مكتوب على التوثيق الرسمي للمصلحة ومتجرب على خادم تجريبي بس، ومتبعتش منه أي إيصال لخادم المصلحة الحقيقي (لا التجربة ولا الفعلي)، ومعندناش اعتماد منها. عشان تشغله بتسجّل جهاز الكاشير وأكواد الأصناف في بوابة المصلحة، وتجرّبه على بيئة التجربة الأول. اسأل محاسبك لو نشاطك مطلوب منه الإيصال الإلكتروني وأنهي نوع إيصال يناسبه.',
        ],
        'en' => [
            'languages' => 'Arabic and English with right-to-left support',
            'payments' => 'Online payments through your own Paymob, Fawry and InstaPay accounts',
            'whatsapp' => 'WhatsApp Business messages from your own account, or a ready WhatsApp message without an account',
            'eta' => 'ETA e-receipt with the QR code on the printed receipt',
            'store' => 'Sync with your Shopify, WooCommerce or Salla store',
            'need' => 'Hosting with PHP 8.3 or newer and an SQLite or MySQL database, plus a cron job every minute (%s). It installs on normal shared hosting (cPanel) with no Redis.',
            'payments_q' => 'Does it take online payments inside the system?',
            'payments_a' => 'Yes. The owner connects their own Paymob (cards and wallets) and Fawry accounts, or types an InstaPay address, and the customer pays %s from a link without signing up. The payment is recorded in the system by itself, like a payment at the desk. The connection follows the gateways\' documentation and is tested against simulated answers only, not against a real account, so try it with a small amount first. Without these accounts, paying at the desk works as before.',
            'eta_q' => 'Is the e-receipt tested against the tax authority?',
            'eta_a' => 'No. The connector follows the official ETA documentation and is tested only against a simulated server. No receipt was sent to the real ETA servers (test or live), and it is not certified by ETA. To use it you register your cash register and your item codes in the ETA portal and try it on the ETA test environment first. Ask your accountant whether your business must issue e-receipts and which receipt type fits it.',
        ],
    ];

    /**
     * What is included in both plans: the languages first, then the connections this system has, in this order.
     *
     * @param  array<int, string>  $connections  any of payments, whatsapp, eta, store
     * @return array<int, string>
     */
    public static function included(string $lang, array $connections): array
    {
        return array_merge([self::TEXTS[$lang]['languages']], array_map(fn (string $key) => self::TEXTS[$lang][$key], $connections));
    }

    /** Hosting needs, with what the every-minute cron job is for in this system. */
    public static function needBody(string $lang, string $cronFor): string
    {
        return sprintf(self::TEXTS[$lang]['need'], $cronFor);
    }

    /** @return array{q: string, a: string} */
    public static function paymentsFaq(string $lang, string $payable): array
    {
        return ['q' => self::TEXTS[$lang]['payments_q'], 'a' => sprintf(self::TEXTS[$lang]['payments_a'], $payable)];
    }

    /** @return array{q: string, a: string} */
    public static function etaFaq(string $lang): array
    {
        return ['q' => self::TEXTS[$lang]['eta_q'], 'a' => self::TEXTS[$lang]['eta_a']];
    }
}
