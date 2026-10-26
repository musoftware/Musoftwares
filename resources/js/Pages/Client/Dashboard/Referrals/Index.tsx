import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Check, Copy, Lock, MessageCircle, Share2 } from 'lucide-react';
import ClientPage, { Card, Section, TextAction } from '@/Components/Client/ClientPage';
import ReferralTabs from './Partials/ReferralTabs';
import { __ } from '@/lib/i18n';

/** Invite friends: the link to share first, then the optional short name and the developer key. */
export default function Index({ referral, commission_percentage, embedKey }: any) {
    const referralLink = `${window.location.origin}/r/${referral?.slug || referral?.key || ''}`;
    const percent = `${((Number(commission_percentage) - 1) * 100).toFixed(0)}%`;
    const [copied, setCopied] = useState<'link' | 'key' | null>(null);
    const hasSlug = !!referral?.slug && referral.slug.trim() !== '';
    const shareText = `${__('client_rewards.referrals.share_text')} ${referralLink}`;
    const canShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

    const { data, setData, post, processing, errors } = useForm({ slug: referral?.slug || '' });

    const copy = async (text: string, what: 'link' | 'key') => {
        try { await navigator.clipboard.writeText(text); } catch { /* the field stays selectable */ }
        setCopied(what);
        setTimeout(() => setCopied(null), 2000);
    };

    return (
        <ClientPage title={__('client_rewards.referrals.title')} hint={__('client_rewards.referrals.hint', { percent })}>
            <ReferralTabs active="link" />

            <Section title={__('client_rewards.referrals.your_link')}>
                <Card className="space-y-4">
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <input value={referralLink} readOnly dir="ltr" aria-label={__('client_rewards.referrals.your_link')}
                            className="w-full rounded-xl border border-black/10 bg-[#f5f5f7] px-3 py-2.5 font-mono text-sm dark:border-white/10 dark:bg-zinc-800" />
                        <button type="button" onClick={() => copy(referralLink, 'link')}
                            className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#0071e3] px-6 text-sm font-semibold text-white hover:bg-[#0077ed]">
                            {copied === 'link' ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                            {copied === 'link' ? __('client_rewards.referrals.copied') : __('client_rewards.referrals.copy')}
                        </button>
                    </div>
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        <a href={`https://wa.me/?text=${encodeURIComponent(shareText)}`} target="_blank" rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 font-semibold text-emerald-700 hover:underline">
                            <MessageCircle className="h-4 w-4" />{__('client_rewards.referrals.share_whatsapp')}
                        </a>
                        {canShare && (
                            <button type="button" onClick={() => navigator.share({ text: shareText }).catch(() => {})}
                                className="inline-flex items-center gap-1.5 font-semibold text-[#0071e3] hover:underline">
                                <Share2 className="h-4 w-4" />{__('client_rewards.referrals.share')}
                            </button>
                        )}
                    </div>
                    <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.referrals.commission', { percent })}</p>
                </Card>
            </Section>

            <Section title={__('client_rewards.referrals.short_link')}>
                <Card>
                    {hasSlug ? (
                        <p className="flex items-center gap-2 text-sm text-[#1d1d1f]/70 dark:text-zinc-300">
                            <Lock className="h-4 w-4 shrink-0" />{__('client_rewards.referrals.short_link_locked')}
                            <span className="font-mono" dir="ltr">/r/{referral.slug}</span>
                        </p>
                    ) : (
                        <form onSubmit={(e) => { e.preventDefault(); post(route('referrals.update_slug'), { preserveScroll: true }); }} className="space-y-2">
                            <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.referrals.short_link_hint')}</p>
                            <div className="flex gap-2">
                                <input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} disabled={processing} dir="ltr"
                                    placeholder="my-store" aria-label={__('client_rewards.referrals.short_link')}
                                    className="w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 font-mono text-sm dark:border-white/10 dark:bg-zinc-800" />
                                <button type="submit" disabled={processing}
                                    className="h-11 shrink-0 rounded-full bg-[#1d1d1f] px-5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-white dark:text-black">
                                    {__('client_rewards.referrals.save')}
                                </button>
                            </div>
                            {errors.slug && <p className="text-sm text-rose-600">{errors.slug}</p>}
                        </form>
                    )}
                </Card>
            </Section>

            <Section title={__('client_rewards.referrals.developers')}>
                <Card className="space-y-3">
                    <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.referrals.embed_hint')}</p>
                    {embedKey ? (
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <input value={embedKey.key} readOnly dir="ltr" aria-label={__('client_rewards.referrals.embed_key')}
                                className="w-full rounded-xl border border-black/10 bg-[#f5f5f7] px-3 py-2.5 font-mono text-sm dark:border-white/10 dark:bg-zinc-800" />
                            <TextAction onClick={() => copy(embedKey.key, 'key')}>
                                {copied === 'key' ? __('client_rewards.referrals.copied') : __('client_rewards.loyalty.copy')}
                            </TextAction>
                        </div>
                    ) : (
                        <TextAction onClick={() => router.post(route('referrals.generate_embed_key'), {}, { preserveScroll: true })}>
                            {__('client_rewards.referrals.make_key')}
                        </TextAction>
                    )}
                </Card>
            </Section>
        </ClientPage>
    );
}
