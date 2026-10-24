import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { t } from '@/Components/Admin/SoftwareStore/t';

type Lang = 'ar' | 'en';

interface LangKit {
    posts: { problem: string; price: string; demo: string };
    whatsapp: string;
    email: { subject: string; body: string };
    hashtags: string[];
}

interface Kit {
    slug: string;
    name_ar: string;
    name_en: string;
    group: 'programs' | 'systems' | 'services';
    url: string;
    links: Record<string, string>;
    ar: LangKit;
    en: LangKit;
}

const GROUP_LABEL: Record<Kit['group'], [string, string]> = {
    programs: ['Desktop program', 'برنامج سطح مكتب'],
    systems: ['Business system', 'نظام إدارة'],
    services: ['Service', 'خدمة'],
};

const CHANNEL_LABEL: Record<string, [string, string]> = {
    facebook: ['Facebook page', 'صفحة فيسبوك'],
    whatsapp: ['WhatsApp', 'واتساب'],
    linkedin: ['LinkedIn', 'لينكدإن'],
    x: ['X', 'إكس'],
    email: ['Email', 'البريد'],
    groups: ['Facebook groups', 'جروبات فيسبوك'],
    copy: ['Copied link', 'لينك منسوخ'],
};

const POST_LABEL: Record<keyof LangKit['posts'], [string, string]> = {
    problem: ['Post that starts with the problem', 'بوست يبدأ بالمشكلة'],
    price: ['Post that starts with the price', 'بوست يبدأ بالسعر'],
    demo: ['Post that shows the product', 'بوست يعرض المنتج'],
};

async function copyText(text: string): Promise<void> {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }
    const box = document.createElement('textarea');
    box.value = text;
    document.body.appendChild(box);
    box.select();
    document.execCommand('copy');
    document.body.removeChild(box);
}

function CopyBlock({ id, title, text, copied, onCopy, rtl }: { id: string; title: string; text: string; copied: string | null; onCopy: (id: string, text: string) => void; rtl: boolean }) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold">{title}</h3>
                <Button type="button" variant="outline" size="sm" onClick={() => onCopy(id, text)}>
                    {copied === id ? t('Copied', 'اتنسخ') : t('Copy', 'نسخ')}
                </Button>
            </div>
            <textarea readOnly value={text} dir={rtl ? 'rtl' : 'ltr'} rows={Math.min(14, text.split('\n').length + 2)} className="w-full rounded-md border bg-background p-3 text-sm leading-6" />
        </div>
    );
}

const WEEKLY: [string, string][] = [
    ['Post one product in 5 Facebook groups of the right business (read each group rules first).', 'انشر منتج واحد في 5 جروبات فيسبوك لنفس نوع النشاط (اقرا قواعد كل جروب الأول).'],
    ['Send the WhatsApp message to 10 business owners you know or met. One person at a time, never a mass message.', 'ابعت رسالة الواتساب لـ10 أصحاب نشاط تعرفهم أو قابلتهم. واحد واحد، ومن غير رسايل جماعية.'],
    ['Ask one customer for a short written review, and add it to the site only after they agree.', 'اطلب من عميل واحد رأي مكتوب قصير، وما تحطوش على الموقع إلا بعد موافقته.'],
    ['Check Google Search Console for new pages and search words.', 'افتح Google Search Console وشوف الصفحات الجديدة وكلمات البحث.'],
    ['Write down which link brought a visit or an order (the utm links below tell you).', 'سجّل أنهي لينك جاب زيارة أو طلب (لينكات utm تحت بتقولك).'],
];

interface RecoveryDiscount {
    percent: number;
    max: number;
    hours: number;
}

export default function Index({ kits, recoveryDiscount }: { kits: Kit[]; recoveryDiscount: RecoveryDiscount }) {
    const [percent, setPercent] = useState<number>(recoveryDiscount.percent);
    const [lang, setLang] = useState<Lang>('ar');
    const [slug, setSlug] = useState<string | null>(kits[0]?.slug ?? null);
    const [copied, setCopied] = useState<string | null>(null);

    const kit = kits.find((item) => item.slug === slug) ?? null;
    const content = kit ? kit[lang] : null;
    const rtl = lang === 'ar';

    const onCopy = async (id: string, text: string) => {
        await copyText(text);
        setCopied(id);
        window.setTimeout(() => setCopied((current) => (current === id ? null : current)), 1800);
    };

    return (
        <AdminSidebarLayout>
            <Head title={t('Marketing Kit', 'أدوات التسويق')} />
            <div className="p-4 sm:p-6 space-y-8 overflow-x-clip">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Marketing Kit', 'أدوات التسويق')}</h1>
                        <p className="text-sm text-muted-foreground mt-1 max-w-2xl">
                            {t(
                                'Ready-to-post text for every product on the website, made from the real product page. Pick a product, pick a language, copy and post. Nothing is sent for you.',
                                'نصوص جاهزة للنشر لكل منتج على الموقع، متعملة من صفحة المنتج الفعلية. اختار المنتج واللغة وانسخ وانشر. مفيش حاجة بتتبعت لوحدها.'
                            )}
                        </p>
                    </div>
                    <div className="flex gap-2" role="group" aria-label={t('Language', 'اللغة')}>
                        <Button type="button" size="sm" variant={lang === 'ar' ? 'default' : 'outline'} onClick={() => setLang('ar')}>العربية</Button>
                        <Button type="button" size="sm" variant={lang === 'en' ? 'default' : 'outline'} onClick={() => setLang('en')}>English</Button>
                    </div>
                </div>

                <section className="rounded-md border p-4 space-y-3">
                    <h2 className="text-lg font-semibold">{t('Discount in the unpaid-order email', 'الخصم في إيميل الطلب غير المدفوع')}</h2>
                    <p className="text-sm text-muted-foreground max-w-2xl">
                        {t(
                            `A customer who started an order and did not pay gets one email. The link in it applies this discount to the same order and works for ${recoveryDiscount.hours} hours. Use 0 to turn it off.`,
                            `العميل اللي بدأ طلب وما دفعش بيوصله إيميل واحد. اللينك اللي فيه بيطبّق الخصم ده على نفس الطلب وبيشتغل ${recoveryDiscount.hours} ساعة. اكتب 0 علشان تقفله.`
                        )}
                    </p>
                    <form
                        className="flex flex-wrap items-end gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            router.put(route('admin.marketing.discount'), { percent }, { preserveScroll: true });
                        }}
                    >
                        <label className="text-sm font-medium" htmlFor="recovery-percent">
                            {t('Percent', 'النسبة')} (0 - {recoveryDiscount.max})
                        </label>
                        <input
                            id="recovery-percent"
                            type="number"
                            min={0}
                            max={recoveryDiscount.max}
                            value={percent}
                            onChange={(event) => setPercent(Number(event.target.value))}
                            className="w-24 rounded-md border bg-background px-3 py-2 text-sm"
                        />
                        <Button type="submit" size="sm">{t('Save', 'حفظ')}</Button>
                    </form>
                </section>

                <section className="rounded-md border p-4 space-y-3">
                    <h2 className="text-lg font-semibold">{t('This week', 'الأسبوع ده')}</h2>
                    <ol className="list-decimal ps-5 space-y-1.5 text-sm">
                        {WEEKLY.map(([en, ar]) => (
                            <li key={en}>{t(en, ar)}</li>
                        ))}
                    </ol>
                    <p className="text-xs text-muted-foreground">
                        {t('Start with Arabic posts for Egypt. Use the English text when you speak to people outside Egypt.', 'ابدأ بالبوستات العربي لمصر. استخدم النص الإنجليزي لما تكلم ناس برا مصر.')}
                    </p>
                </section>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Product', 'المنتج')}</TableHead>
                                <TableHead>{t('Type', 'النوع')}</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {kits.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={3} className="text-center text-muted-foreground">
                                        {t('No visible products yet.', 'مفيش منتجات ظاهرة لسه.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {kits.map((item) => (
                                <TableRow key={item.slug} className={item.slug === slug ? 'bg-muted/50' : undefined}>
                                    <TableCell>
                                        <div className="font-medium">{item.name_en}</div>
                                        <div className="text-xs text-muted-foreground">{item.name_ar}</div>
                                    </TableCell>
                                    <TableCell>{t(...GROUP_LABEL[item.group])}</TableCell>
                                    <TableCell className="text-end">
                                        <Button type="button" size="sm" variant={item.slug === slug ? 'default' : 'outline'} onClick={() => setSlug(item.slug)}>
                                            {t('Open kit', 'افتح الأدوات')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {kit && content && (
                    <section className="space-y-6 rounded-md border p-4 sm:p-6">
                        <h2 className="text-lg font-semibold">{lang === 'ar' ? kit.name_ar : kit.name_en}</h2>

                        {(Object.keys(content.posts) as (keyof LangKit['posts'])[]).map((variant) => (
                            <CopyBlock key={variant} id={`post-${variant}`} title={t(...POST_LABEL[variant])} text={content.posts[variant]} copied={copied} onCopy={onCopy} rtl={rtl} />
                        ))}

                        <CopyBlock id="whatsapp" title={t('WhatsApp message (send to one person at a time)', 'رسالة واتساب (ابعتها لواحد واحد)')} text={content.whatsapp} copied={copied} onCopy={onCopy} rtl={rtl} />
                        <CopyBlock id="email-subject" title={t('Email subject', 'عنوان الإيميل')} text={content.email.subject} copied={copied} onCopy={onCopy} rtl={rtl} />
                        <CopyBlock id="email-body" title={t('Email text', 'نص الإيميل')} text={content.email.body} copied={copied} onCopy={onCopy} rtl={rtl} />
                        <CopyBlock id="hashtags" title={t('Hashtags', 'الهاشتاجات')} text={content.hashtags.join(' ')} copied={copied} onCopy={onCopy} rtl={rtl} />

                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold">{t('Tracked links (use the right one for each place)', 'لينكات متتبّعة (استخدم اللينك المناسب لكل مكان)')}</h3>
                            <div className="overflow-x-auto rounded-md border">
                                <Table>
                                    <TableBody>
                                        {Object.entries(kit.links).map(([channel, link]) => (
                                            <TableRow key={channel}>
                                                <TableCell className="whitespace-nowrap">{t(...(CHANNEL_LABEL[channel] ?? [channel, channel]))}</TableCell>
                                                <TableCell className="break-all text-xs" dir="ltr">{link}</TableCell>
                                                <TableCell className="text-end">
                                                    <Button type="button" variant="outline" size="sm" onClick={() => onCopy(`link-${channel}`, link)}>
                                                        {copied === `link-${channel}` ? t('Copied', 'اتنسخ') : t('Copy', 'نسخ')}
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </section>
                )}
            </div>
        </AdminSidebarLayout>
    );
}
