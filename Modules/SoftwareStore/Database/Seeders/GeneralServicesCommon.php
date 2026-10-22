<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * Page text shared by every general service: the way we work, what every plan includes, the refund and renewal rules.
 * The refund and renewal wording is the owner's commercial policy in plain words. A lawyer must read it before launch.
 */
class GeneralServicesCommon
{
    /** @param 'once'|'monthly' $billing */
    public static function for(string $lang, string $billing): array
    {
        $text = $lang === 'ar' ? self::arabic() : self::english();

        return $text['shared'] + [
            'steps' => $text['steps'][$billing],
            'included' => $text['included'][$billing],
            'plans_note' => $text['plans_note'][$billing],
            'terms' => $text['terms'][$billing],
            'after_body' => $text['after_body'][$billing],
            'faq' => array_merge($text['faq'][$billing], $text['faq']['all']),
        ];
    }

    /** One price plan with its included lines in both languages. */
    public static function plan(string $key, array $names, int $price, array $descriptions, array $english, array $arabic): array
    {
        return [
            'key' => $key, 'name' => $names[0], 'name_ar' => $names[1], 'price' => $price,
            'description' => $descriptions[0], 'description_ar' => $descriptions[1],
            'features' => $english, 'features_ar' => $arabic,
        ];
    }

    private static function english(): array
    {
        return [
            'shared' => [
                'overview_title' => 'Understand everything in one minute', 'who' => 'Who is it for?', 'need' => 'What do we need from you?', 'after' => 'What happens after payment?',
                'problems_kicker' => 'Common problems', 'pillars_kicker' => 'What you get', 'video_kicker' => 'See how it works', 'video_title' => 'How the service works, step by step',
                'steps_kicker' => 'How we work', 'steps_title' => 'From payment to handover in 4 steps', 'gallery_title' => 'The service in pictures',
                'plans_kicker' => 'Plans', 'plans_title' => 'Choose a plan', 'details_title' => 'Service details', 'list_title' => 'What the plans cover', 'faq_title' => 'Common questions',
                'cta_kicker' => 'Start today', 'cta_body' => 'Choose a plan and pay online. After payment send us your order number on WhatsApp and we start.', 'cta_button' => 'Continue to payment',
            ],
            'after_body' => [
                'once' => 'After payment you send us your order number on WhatsApp (the button appears after payment). Within 1 to 2 working days we send a short brief form, and we agree a start date with you.',
                'monthly' => 'After payment you send us your order number on WhatsApp (the button appears after payment). Within 1 to 2 working days we send a short brief form and ask for the access we need, then the first month starts.',
            ],
            'steps' => [
                'once' => [
                    ['title' => 'Pay online', 'body' => 'Choose a plan and pay by card. No account is needed on our site.'],
                    ['title' => 'Brief and written scope', 'body' => 'Send your order number on WhatsApp. We send a short brief, then a written scope that you approve before work starts.'],
                    ['title' => 'Previews and revisions', 'body' => 'You see a preview at each stage and send your feedback in one message. The plan says how many revision rounds it includes.'],
                    ['title' => 'Handover', 'body' => 'Final files and logins are handed over once you have paid in full, followed by a short support period for fixes.'],
                ],
                'monthly' => [
                    ['title' => 'Pay the first month', 'body' => 'Choose a plan and pay by card. No account is needed on our site.'],
                    ['title' => 'Brief and access', 'body' => 'Send your order number on WhatsApp. We send a short brief and ask for the access we need to your accounts.'],
                    ['title' => 'Monthly plan and work', 'body' => 'We agree the month plan in writing, do the work and send you a report at the end of the month.'],
                    ['title' => 'Renew or stop', 'body' => 'Before each new month we send a payment link on WhatsApp. If you do not pay, the service simply stops.'],
                ],
            ],
            'included' => [
                'once' => ['A written scope you approve before work starts', 'Ownership passes to you when you pay in full', 'Final files and logins handed over'],
                'monthly' => ['A written monthly plan you approve', 'A report at the end of every month', 'Stop any time before the next month starts'],
            ],
            'plans_note' => [
                'once' => 'Prices are in Egyptian pounds and are paid once. Extra pages, extra revision rounds and anything outside the plan are quoted before we do them.',
                'monthly' => 'Prices are in Egyptian pounds per month. You pay the first month here. Each next month we send a payment link on WhatsApp, and you can stop before it starts. Anything outside the plan is quoted before we do it.',
            ],
            'terms' => [
                'once' => 'Refunds: before work starts, ask within 7 days of payment for a full refund. After work starts, we refund what is left after the work already done, by stage. Work already delivered at your request is not refundable. Your written scope shows the stages.',
                'monthly' => 'Refunds: if you ask before we start work, you get the month back in full. After work starts, the month is not refunded, but you can stop before the next month. We do not promise results such as followers, ranking or sales.',
            ],
            'faq' => [
                'once' => [
                    ['q' => 'What if I cancel?', 'a' => 'Before we start work you can ask for a full refund within 7 days of payment. After kickoff we refund what is left after the work already done, by stage. Work already delivered at your request is not refundable. The stages are written in your scope.'],
                    ['q' => 'Who owns the final work?', 'a' => 'You do. Ownership passes to you when you have paid in full, and we hand over the files and logins. We keep the right to show the finished work in our portfolio unless you ask us not to.'],
                ],
                'monthly' => [
                    ['q' => 'What if I want to stop?', 'a' => 'Message us before the next month starts and we stop. If you ask before we start work, you get the first month back in full. Once work has started, that month is not refunded.'],
                    ['q' => 'Do I get a contract for a fixed time?', 'a' => 'No. It is month by month. Each month you get a written plan and an end of month report.'],
                ],
                'all' => [
                    ['q' => 'Do you promise results?', 'a' => 'We promise the work listed in the plan, delivered on time. We do not promise rankings, followers, leads or sales, because nobody honest can. Results also depend on your market, your offer and your budget.'],
                    ['q' => 'How do I pay, and is the price final?', 'a' => 'You pay online by card with no account. The price on this page is what you pay for the listed work. Costs paid to other companies, such as domains, hosting, platform fees or ad spend, are listed under what is not included.'],
                    ['q' => 'What if I need something custom?', 'a' => 'Message us on WhatsApp with a short description. We reply with a written offer and a fixed price before any work starts.'],
                ],
            ],
        ];
    }

    private static function arabic(): array
    {
        return [
            'shared' => [
                'overview_title' => 'اعرف كل حاجة في دقيقة', 'who' => 'مناسب لمين؟', 'need' => 'محتاجين منك إيه؟', 'after' => 'بعد الدفع إيه اللي بيحصل؟',
                'problems_kicker' => 'مشاكل شائعة', 'pillars_kicker' => 'اللي هتحصل عليه', 'video_kicker' => 'شوف إزاي بنشتغل', 'video_title' => 'إزاي الخدمة بتتم خطوة بخطوة',
                'steps_kicker' => 'طريقة الشغل', 'steps_title' => 'من الدفع للتسليم في 4 خطوات', 'gallery_title' => 'الخدمة في صور',
                'plans_kicker' => 'الباقات', 'plans_title' => 'اختار الباقة', 'details_title' => 'تفاصيل الخدمة', 'list_title' => 'اللي الباقات بتغطيه', 'faq_title' => 'أسئلة شائعة',
                'cta_kicker' => 'ابدأ النهارده', 'cta_body' => 'اختار الباقة وادفع أونلاين. بعد الدفع ابعتلنا رقم طلبك على الواتساب ونبدأ.', 'cta_button' => 'كمّل للدفع',
            ],
            'after_body' => [
                'once' => 'بعد الدفع بتبعتلنا رقم طلبك على الواتساب (الزرار بيظهر بعد الدفع). في خلال يوم أو يومين شغل بنبعتلك استمارة قصيرة، ونتفق على ميعاد البداية.',
                'monthly' => 'بعد الدفع بتبعتلنا رقم طلبك على الواتساب (الزرار بيظهر بعد الدفع). في خلال يوم أو يومين شغل بنبعتلك استمارة قصيرة ونطلب الصلاحيات اللي محتاجينها، وبعدها الشهر الأول بيبدأ.',
            ],
            'steps' => [
                'once' => [
                    ['title' => 'ادفع أونلاين', 'body' => 'اختار الباقة وادفع بالكارت. من غير ما تعمل حساب على موقعنا.'],
                    ['title' => 'استمارة ونطاق شغل مكتوب', 'body' => 'ابعت رقم طلبك على الواتساب. بنبعتلك استمارة قصيرة، وبعدها نطاق الشغل مكتوب توافق عليه قبل ما نبدأ.'],
                    ['title' => 'معاينات وتعديلات', 'body' => 'بتشوف معاينة في كل مرحلة وتبعت ملاحظاتك في رسالة واحدة. الباقة بتحدد عدد جولات التعديل.'],
                    ['title' => 'التسليم', 'body' => 'الملفات وبيانات الدخول بتتسلمك بعد ما تدفع كامل المبلغ، وبعدها فترة دعم قصيرة للإصلاحات.'],
                ],
                'monthly' => [
                    ['title' => 'ادفع الشهر الأول', 'body' => 'اختار الباقة وادفع بالكارت. من غير ما تعمل حساب على موقعنا.'],
                    ['title' => 'استمارة وصلاحيات', 'body' => 'ابعت رقم طلبك على الواتساب. بنبعتلك استمارة قصيرة ونطلب الصلاحيات المطلوبة على حساباتك.'],
                    ['title' => 'خطة الشهر والشغل', 'body' => 'بنتفق على خطة الشهر مكتوبة، وننفذ، وفي آخر الشهر بنبعتلك تقرير.'],
                    ['title' => 'تجديد أو إيقاف', 'body' => 'قبل كل شهر جديد بنبعتلك لينك دفع على الواتساب. لو مادفعتش، الخدمة بتقف ببساطة.'],
                ],
            ],
            'included' => [
                'once' => ['نطاق شغل مكتوب بتوافق عليه قبل البداية', 'الملكية بتنتقلك لما تدفع كامل المبلغ', 'تسليم الملفات النهائية وبيانات الدخول'],
                'monthly' => ['خطة شهرية مكتوبة بتوافق عليها', 'تقرير في آخر كل شهر', 'تقدر توقف قبل بداية أي شهر جديد'],
            ],
            'plans_note' => [
                'once' => 'الأسعار بالجنيه المصري ودفعة واحدة. أي صفحات زيادة أو جولات تعديل زيادة أو شغل برة الباقة بنقولك سعره قبل ما ننفذه.',
                'monthly' => 'الأسعار بالجنيه المصري في الشهر. بتدفع الشهر الأول هنا، وكل شهر بعده بنبعتلك لينك دفع على الواتساب، وتقدر توقف قبل ما يبدأ. أي شغل برة الباقة بنقولك سعره قبل ما ننفذه.',
            ],
            'terms' => [
                'once' => 'الاسترداد: قبل بداية الشغل تقدر تطلب استرداد كامل خلال 7 أيام من الدفع. بعد بداية الشغل بنسترد الباقي بعد خصم الشغل المنفذ، على حسب المرحلة. الشغل اللي اتسلم بطلبك مش بيتسترد. نطاق الشغل المكتوب فيه المراحل.',
                'monthly' => 'الاسترداد: لو طلبت قبل ما نبدأ الشغل بتاخد الشهر كامل. بعد بداية الشغل الشهر مش بيتسترد، لكن تقدر توقف قبل الشهر الجاي. إحنا مش بنوعد بنتايج زي متابعين أو ترتيب أو مبيعات.',
            ],
            'faq' => [
                'once' => [
                    ['q' => 'لو ألغيت الطلب؟', 'a' => 'قبل ما نبدأ الشغل تقدر تطلب استرداد كامل خلال 7 أيام من الدفع. بعد البداية بنسترد الباقي بعد خصم الشغل المنفذ على حسب المرحلة. الشغل اللي اتسلم بطلبك مش بيتسترد. المراحل مكتوبة في نطاق الشغل.'],
                    ['q' => 'مين اللي يملك الشغل النهائي؟', 'a' => 'انت. الملكية بتنتقلك لما تدفع كامل المبلغ، وبنسلمك الملفات وبيانات الدخول. بنحتفظ بحق عرض الشغل في معرض أعمالنا إلا لو طلبت غير كده.'],
                ],
                'monthly' => [
                    ['q' => 'لو عايز أوقف الخدمة؟', 'a' => 'ابعتلنا قبل ما الشهر الجاي يبدأ ونوقف. لو طلبت قبل ما نبدأ الشغل بتاخد الشهر الأول كامل. بعد بداية الشغل الشهر ده مش بيتسترد.'],
                    ['q' => 'في عقد لمدة محددة؟', 'a' => 'لا. الشغل شهر بشهر. كل شهر بتاخد خطة مكتوبة وتقرير في آخره.'],
                ],
                'all' => [
                    ['q' => 'بتوعدوا بنتايج؟', 'a' => 'بنوعد بالشغل المكتوب في الباقة بيتسلم في وقته. مش بنوعد بترتيب أو متابعين أو عملاء أو مبيعات، لأن مفيش حد أمين يقدر يوعد بكده. النتايج بتعتمد كمان على سوقك وعرضك وميزانيتك.'],
                    ['q' => 'أدفع إزاي، والسعر نهائي؟', 'a' => 'بتدفع أونلاين بالكارت من غير حساب. السعر في الصفحة هو اللي بتدفعه للشغل المكتوب. أي مصاريف بتتدفع لشركات تانية زي الدومين والاستضافة ورسوم المنصات والإعلانات مكتوبة تحت "غير مشمول".'],
                    ['q' => 'لو عايز حاجة مخصوصة؟', 'a' => 'ابعتلنا وصف قصير على الواتساب. بنرد بعرض مكتوب وسعر ثابت قبل ما نبدأ أي شغل.'],
                ],
            ],
        ];
    }
}
