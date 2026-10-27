import { FormEvent, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Check, Copy, ExternalLink, Store } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface Partner {
    brand_name: string;
    slug: string;
    logo_url: string | null;
    accent_color: string;
    support_whatsapp: string | null;
    support_email: string | null;
    website_url: string | null;
    is_active: boolean;
    expires_at: string | null;
    store_url: string;
    iframe: string;
}

interface PriceRow {
    key: string;
    name: string;
    base: number;
    base_label: string;
    currency: string;
    price: number | null;
}

interface Sale {
    id: number;
    date: string;
    program: string | null;
    customer: string;
    amount: string;
    share: string;
    paid: boolean;
}

const money = (value: number) => value.toLocaleString(undefined, { maximumFractionDigits: 2 });

/**
 * White label for partners: the subscription first, then their brand and prices, the code for
 * their website, and their sales.
 */
export default function WhiteLabelIndex({ partner, suggestedSlug, rows, sales, wallet, commissionPercent }: {
    partner: Partner | null;
    suggestedSlug: string;
    rows: PriceRow[];
    sales: Sale[];
    wallet: string;
    commissionPercent: number;
}) {
    const { data, setData, post, processing, errors } = useForm({
        brand_name: partner?.brand_name ?? '',
        slug: partner?.slug ?? suggestedSlug,
        accent_color: partner?.accent_color ?? '#0071e3',
        support_whatsapp: partner?.support_whatsapp ?? '',
        support_email: partner?.support_email ?? '',
        website_url: partner?.website_url ?? '',
        logo: null as File | null,
        prices: Object.fromEntries(rows.map((row) => [row.key, row.price != null ? String(row.price) : ''])) as Record<string, string>,
    });
    const [copied, setCopied] = useState<string | null>(null);

    const save = (e: FormEvent) => {
        e.preventDefault();
        post('/white-label/settings', { forceFormData: true, preserveScroll: true });
    };

    const copy = async (key: string, text: string) => {
        try { await navigator.clipboard.writeText(text); } catch { /* the field stays selectable */ }
        setCopied(key);
        setTimeout(() => setCopied(null), 2000);
    };

    const earn = (row: PriceRow) => {
        const price = Math.max(row.base, Number(data.prices[row.key]) || 0);
        return row.base * commissionPercent / 100 + (price - row.base);
    };

    const text = (field: 'brand_name' | 'slug' | 'support_whatsapp' | 'support_email' | 'website_url', type = 'text', hint?: string) => (
        <div>
            <Label htmlFor={field}>{__(`white_label.${field}`)}</Label>
            <Input id={field} type={type} value={data[field]} onChange={(e) => setData(field, e.target.value)} className="mt-1" dir={field === 'brand_name' ? undefined : 'ltr'} />
            {hint && <p className="mt-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{hint}</p>}
            {errors[field] && <p className="mt-1 text-sm text-rose-600">{errors[field]}</p>}
        </div>
    );

    return (
        <ClientPage
            title={__('white_label.title')}
            hint={__('white_label.settings_hint')}
            action={<TextAction href="/white-label">{__('white_label.steps_title')}</TextAction>}
        >
            {/* Subscription */}
            <Card className="flex flex-wrap items-center justify-between gap-4">
                <div className="min-w-0">
                    <p className={cn('font-semibold', partner?.is_active ? 'text-emerald-700 dark:text-emerald-400' : '')}>
                        {partner?.is_active ? __('white_label.status_active', { date: partner.expires_at ?? '' }) : __('white_label.status_inactive')}
                    </p>
                    <p className="mt-1 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                        {__('white_label.wallet', { amount: wallet })} · <TextAction href="/financial/add-balance">{__('white_label.add_balance')}</TextAction>
                    </p>
                </div>
                {partner ? (
                    <PrimaryAction onClick={() => router.post('/white-label/subscribe', {}, { preserveScroll: true })}>
                        {partner.is_active ? __('white_label.renew') : __('white_label.subscribe')}
                    </PrimaryAction>
                ) : (
                    <span className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('white_label.save_brand_first')}</span>
                )}
            </Card>

            <form onSubmit={save} className="space-y-8">
                <Section title={__('white_label.brand_section')}>
                    <Card className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {text('brand_name')}
                        {text('slug', 'text', __('white_label.slug_hint'))}
                        <div>
                            <Label htmlFor="logo">{__('white_label.logo')}</Label>
                            <div className="mt-1 flex items-center gap-3">
                                {partner?.logo_url && <img src={partner.logo_url} alt="" className="h-10 w-10 rounded-lg border border-black/5 object-contain" />}
                                <Input id="logo" type="file" accept="image/png,image/jpeg,image/webp" onChange={(e) => setData('logo', e.target.files?.[0] ?? null)} />
                            </div>
                            <p className="mt-1 text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('white_label.logo_hint')}</p>
                            {errors.logo && <p className="mt-1 text-sm text-rose-600">{errors.logo}</p>}
                        </div>
                        <div>
                            <Label htmlFor="accent_color">{__('white_label.accent_color')}</Label>
                            <div className="mt-1 flex items-center gap-3">
                                <input id="accent_color" type="color" value={data.accent_color} onChange={(e) => setData('accent_color', e.target.value)} className="h-10 w-14 cursor-pointer rounded-lg border border-black/10" />
                                <span className="font-mono text-sm" dir="ltr">{data.accent_color}</span>
                            </div>
                            {errors.accent_color && <p className="mt-1 text-sm text-rose-600">{errors.accent_color}</p>}
                        </div>
                        {text('support_whatsapp', 'tel')}
                        {text('support_email', 'email')}
                        {text('website_url', 'url')}
                    </Card>
                </Section>

                <Section title={__('white_label.prices_section')}>
                    <p className="-mt-1 mb-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('white_label.prices_hint')}</p>
                    <Card className="divide-y divide-black/5 p-0 sm:p-0 dark:divide-white/10">
                        {rows.map((row) => (
                            <div key={row.key} className="grid grid-cols-2 items-center gap-3 p-4 sm:grid-cols-[1fr_120px_140px_110px]">
                                <span className="col-span-2 font-medium sm:col-span-1">{row.name}</span>
                                <span className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('white_label.our_price')}: <span dir="ltr">{row.base_label}</span></span>
                                <div>
                                    <Input
                                        type="number" min={row.base} step="any" dir="ltr" placeholder={String(row.base)} aria-label={`${__('white_label.your_price')} ${row.name}`}
                                        value={data.prices[row.key]} onChange={(e) => setData('prices', { ...data.prices, [row.key]: e.target.value })}
                                    />
                                    {errors[`prices.${row.key}` as keyof typeof errors] && <p className="mt-1 text-xs text-rose-600">{errors[`prices.${row.key}` as keyof typeof errors]}</p>}
                                </div>
                                <span className="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{__('white_label.you_earn')}: <span dir="ltr">{money(earn(row))} {row.currency}</span></span>
                            </div>
                        ))}
                    </Card>
                </Section>

                <div className="flex justify-end">
                    <button type="submit" disabled={processing} className="inline-flex h-11 items-center justify-center rounded-full bg-[#0071e3] px-8 text-sm font-semibold text-white shadow-sm hover:bg-[#0077ed] disabled:opacity-60">
                        {__('white_label.save')}
                    </button>
                </div>
            </form>

            {partner && (
                <Section title={__('white_label.embed_section')}>
                    <Card className="space-y-4">
                        <div>
                            <Label>{__('white_label.store_link')}</Label>
                            <div className="mt-1 flex items-center gap-2">
                                <Input readOnly value={partner.store_url} dir="ltr" onFocus={(e) => e.target.select()} />
                                <button type="button" onClick={() => copy('link', partner.store_url)} className="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-black/10 px-4 text-sm font-semibold dark:border-white/10">
                                    {copied === 'link' ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}{copied === 'link' ? __('white_label.copied') : __('white_label.copy')}
                                </button>
                                <a href={partner.store_url} target="_blank" rel="noopener noreferrer" aria-label={__('white_label.open_store')} className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-black/10 dark:border-white/10">
                                    <ExternalLink className="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                        <div>
                            <Label>{__('white_label.embed_hint')}</Label>
                            <textarea readOnly value={partner.iframe} dir="ltr" rows={3} onFocus={(e) => e.target.select()} className="mt-1 w-full rounded-xl border border-black/10 bg-[#f5f5f7] p-3 font-mono text-xs dark:border-white/10 dark:bg-zinc-800" />
                            <button type="button" onClick={() => copy('iframe', partner.iframe)} className="mt-2 inline-flex h-10 items-center gap-1.5 rounded-full bg-[#1d1d1f] px-5 text-sm font-semibold text-white">
                                {copied === 'iframe' ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}{copied === 'iframe' ? __('white_label.copied') : __('white_label.copy')}
                            </button>
                        </div>
                    </Card>
                </Section>
            )}

            <Section title={__('white_label.sales_section')}>
                {sales.length === 0 ? (
                    <EmptyNote icon={<Store className="h-5 w-5" />}>{__('white_label.no_sales')}</EmptyNote>
                ) : (
                    <Card className="divide-y divide-black/5 p-0 sm:p-0 dark:divide-white/10">
                        {sales.map((sale) => (
                            <div key={sale.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
                                <div className="min-w-0">
                                    <p className="font-medium">{sale.program}</p>
                                    <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{sale.date} · {sale.customer}</p>
                                </div>
                                <div className="text-end text-sm">
                                    <p dir="ltr">{sale.amount}</p>
                                    <p className={sale.paid ? 'font-semibold text-emerald-700 dark:text-emerald-400' : 'text-[#1d1d1f]/50 dark:text-zinc-500'}>
                                        {sale.paid ? <>{__('white_label.your_share')}: <span dir="ltr">{sale.share}</span></> : __('white_label.waiting')}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </Card>
                )}
            </Section>
        </ClientPage>
    );
}
