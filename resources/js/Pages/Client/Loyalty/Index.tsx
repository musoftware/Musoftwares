import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Award, Check, CheckCircle2, Clock, Copy, Gift, MessageSquare, Shield, Users, Zap } from 'lucide-react';
import ClientPage, { Card, EmptyNote, PrimaryAction, Section, TextAction } from '@/Components/Client/ClientPage';
import MonthlyRanking, { MonthlyRankingData } from '@/Components/Client/MonthlyRanking';
import { __ } from '@/lib/i18n';
import { cn, formatMoney } from '@/lib/utils';

interface LoyaltyTier {
    id: number;
    name: string;
    slug?: string;
    discount_percentage?: number | string;
    min_lifetime_points: number;
    perks_payload?: { perks?: string[] };
}

interface LedgerEntry {
    id: number;
    title: string;
    points: number;
    balance_after: number;
    metadata?: any;
    date: string;
}

interface RewardItem {
    id: number;
    name: string;
    description: string;
    points_cost: number;
}

interface LoyaltyPageProps {
    summary: {
        balance: number;
        lifetime_points: number;
        current_tier: LoyaltyTier | null;
        next_tier: LoyaltyTier | null;
        points_to_next_tier: number;
        progress_percentage: number;
        tier: string;
    };
    ledger: { data: LedgerEntry[]; current_page: number; last_page: number; prev_page_url?: string | null; next_page_url?: string | null };
    tiers: LoyaltyTier[];
    rewards: RewardItem[];
    referral: { url: string; total_users: number; points_earned: number };
    pointsConversionRate: number;
    monthlyRanking?: MonthlyRankingData;
}

/** Translated level name ("Gold" / "ذهبي"), falling back to the name stored with the level. */
function tierName(tier: LoyaltyTier | null | undefined, fallbackSlug = 'bronze'): string {
    const slug = (tier?.slug || tier?.name || fallbackSlug).toLowerCase();
    const key = `client_home.tiers.${slug}`;
    const text = __(key);
    return text !== key ? text : tier?.name || slug;
}

/**
 * Rewards: the badge and points first, then this month's ranking, what the points buy, the points
 * history, the levels, how to earn, and the invite link.
 */
export default function LoyaltyIndex({ summary, ledger, tiers = [], rewards = [], referral, pointsConversionRate = 1 / 30, monthlyRanking }: LoyaltyPageProps) {
    const { auth } = usePage().props as any;
    const [balance, setBalance] = useState<number>(summary?.balance || 0);
    const [redeemingId, setRedeemingId] = useState<number | null>(null);
    const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
    const [filter, setFilter] = useState<'all' | 'earned' | 'spent'>('all');
    const [copied, setCopied] = useState(false);

    const tierSlug = (summary?.current_tier?.slug || summary?.tier || 'bronze').toLowerCase();
    const flash = (ok: boolean, text: string) => { setMessage({ ok, text }); setTimeout(() => setMessage(null), 5000); };

    const redeem = async (reward: RewardItem) => {
        if (balance < reward.points_cost) return flash(false, __('loyalty.insufficient_points'));
        try {
            setRedeemingId(reward.id);
            const res = await axios.post(`/api/portal/loyalty/rewards/${reward.id}/redeem`);
            if (res.data?.status === 'success') {
                setBalance(res.data.data.new_points_balance);
                flash(true, __('client.loyalty_page_redeem_success', { name: reward.name }));
            }
        } catch (err: any) {
            flash(false, err?.response?.data?.message || __('client.loyalty_page_redeem_failed'));
        } finally {
            setRedeemingId(null);
        }
    };

    const copyLink = async () => {
        try { await navigator.clipboard.writeText(referral?.url || ''); } catch { /* field stays selectable */ }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const history = (ledger?.data || []).filter((item) => (filter === 'earned' ? item.points > 0 : filter === 'spent' ? item.points < 0 : true));
    const ways = [
        { icon: Clock, title: 'client.loyalty_page_early_title', desc: 'client.loyalty_page_early_desc' },
        { icon: MessageSquare, title: 'client.loyalty_page_tickets_title', desc: 'client.loyalty_page_tickets_desc' },
        { icon: Zap, title: 'client.loyalty_page_profile_title', desc: 'client.loyalty_page_profile_desc' },
        { icon: Users, title: 'client.loyalty_page_referral_title', desc: 'client.loyalty_page_referral_desc' },
        { icon: Shield, title: 'client.loyalty_page_welcome_title', desc: 'client.loyalty_page_welcome_desc' },
        { icon: Award, title: 'client.loyalty_page_courtesy_title', desc: 'client.loyalty_page_courtesy_desc' },
    ];

    return (
        <ClientPage
            title={__('client_rewards.loyalty.title')}
            hint={__('client_rewards.loyalty.hint')}
            action={rewards.length > 0 ? <PrimaryAction href="#rewards" icon={<Gift className="h-4 w-4" />}>{__('client_rewards.loyalty.use_points')}</PrimaryAction> : undefined}
        >
            {message && (
                <div className={cn('flex items-center gap-2 rounded-2xl p-4 text-sm', message.ok ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-rose-50 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200')}>
                    {message.ok ? <CheckCircle2 className="h-4 w-4 shrink-0" /> : <Shield className="h-4 w-4 shrink-0" />}{message.text}
                </div>
            )}

            {/* Badge, points and progress */}
            <Card>
                <div className="flex items-center gap-4">
                    <img src={`/images/tiers/${tierSlug}.svg`} alt="" className="h-20 w-20 shrink-0" />
                    <div className="min-w-0">
                        <p className="text-2xl font-bold">{tierName(summary?.current_tier, tierSlug)}</p>
                        <p className="text-sm text-[#1d1d1f]/70 dark:text-zinc-300">{__('client_rewards.loyalty.points', { count: balance.toLocaleString('en-US') })}</p>
                        <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                            {__('client_rewards.loyalty.worth', { amount: formatMoney(+(balance * pointsConversionRate).toFixed(2), auth?.user?.currency) })}
                        </p>
                    </div>
                </div>
                <div className="mt-5">
                    <div className="h-2 overflow-hidden rounded-full bg-[#f5f5f7] dark:bg-zinc-800" role="progressbar" aria-valuenow={summary?.progress_percentage || 0} aria-valuemin={0} aria-valuemax={100}>
                        <div className="h-full rounded-full bg-[#0071e3]" style={{ width: `${Math.min(100, Math.max(0, summary?.progress_percentage || 0))}%` }} />
                    </div>
                    <p className="mt-2 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                        {summary?.next_tier
                            ? __('client_rewards.loyalty.to_next', { points: (summary.points_to_next_tier || 0).toLocaleString('en-US'), tier: tierName(summary.next_tier) })
                            : __('client_rewards.loyalty.top_tier')}
                        {' · '}{__('client_rewards.loyalty.lifetime', { count: (summary?.lifetime_points || 0).toLocaleString('en-US') })}
                    </p>
                </div>
            </Card>

            {/* Monthly ranking: other clients are anonymous */}
            {monthlyRanking && (
                <div id="ranking" className="scroll-mt-24">
                    <Section title={__('client_home.rewards.ranking_title')}>
                        <Card>
                            <p className="mb-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_home.rewards.ranking_hint')}</p>
                            {monthlyRanking.me.position !== null && (
                                <p className="mb-3 text-sm font-semibold text-[#0071e3]">
                                    {__('client_home.rewards.rank', { position: monthlyRanking.me.position, total: monthlyRanking.participants })} · {__('client_home.rewards.top_percent', { percent: monthlyRanking.me.top_percent ?? 100 })}
                                </p>
                            )}
                            <MonthlyRanking ranking={monthlyRanking} />
                        </Card>
                    </Section>
                </div>
            )}

            {/* What the points buy */}
            <div id="rewards" className="scroll-mt-24">
                <Section title={__('client_rewards.loyalty.rewards')}>
                    <p className="-mt-1 mb-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.loyalty.rewards_hint')}</p>
                    {rewards.length === 0 ? (
                        <EmptyNote icon={<Gift className="h-5 w-5" />}>{__('client_rewards.loyalty.no_rewards')}</EmptyNote>
                    ) : (
                        <ul className="space-y-3">
                            {rewards.map((reward) => {
                                const canAfford = balance >= reward.points_cost;
                                return (
                                    <li key={reward.id}>
                                        <Card className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="min-w-0">
                                                <h3 className="font-bold">{reward.name}</h3>
                                                {reward.description && <p className="mt-0.5 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{reward.description}</p>}
                                                <p className="mt-1 text-sm font-semibold text-[#0071e3]">{__('client_rewards.loyalty.points', { count: reward.points_cost.toLocaleString('en-US') })}</p>
                                            </div>
                                            {canAfford ? (
                                                <PrimaryAction onClick={() => redeem(reward)} className={redeemingId === reward.id ? 'pointer-events-none opacity-60' : ''}>
                                                    {redeemingId === reward.id ? __('client_rewards.loyalty.redeeming') : __('client_rewards.loyalty.redeem')}
                                                </PrimaryAction>
                                            ) : (
                                                <span className="text-sm text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_rewards.loyalty.need_more', { points: (reward.points_cost - balance).toLocaleString('en-US') })}</span>
                                            )}
                                        </Card>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Section>
            </div>

            {/* Points history */}
            <Section
                title={__('client_rewards.loyalty.history')}
                action={
                    <div className="flex gap-1 rounded-full bg-black/[0.04] p-1 text-xs font-semibold dark:bg-white/[0.06]">
                        {(['all', 'earned', 'spent'] as const).map((f) => (
                            <button key={f} type="button" onClick={() => setFilter(f)}
                                className={cn('rounded-full px-3 py-1', filter === f ? 'bg-white shadow-sm dark:bg-zinc-800' : 'text-[#1d1d1f]/60 dark:text-zinc-400')}>
                                {__(`client_rewards.loyalty.filter_${f}`)}
                            </button>
                        ))}
                    </div>
                }
            >
                {history.length === 0 ? (
                    <EmptyNote>{__('client_rewards.loyalty.no_history')}</EmptyNote>
                ) : (
                    <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                        {history.map((item) => (
                            <div key={item.id} className="flex items-center gap-3 px-4 py-3 sm:px-5">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">{item.title}</p>
                                    <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">
                                        <span dir="ltr">{item.date}</span>
                                        {item.metadata?.reason ? ` · ${item.metadata.reason}` : ''}
                                    </p>
                                </div>
                                <div className="shrink-0 text-end">
                                    <p className={cn('font-bold', item.points > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#1d1d1f]/70 dark:text-zinc-300')} dir="ltr">
                                        {item.points > 0 ? `+${item.points}` : item.points}
                                    </p>
                                    <p className="text-xs text-[#1d1d1f]/50 dark:text-zinc-500">{__('client_rewards.loyalty.balance_after', { points: item.balance_after.toLocaleString('en-US') })}</p>
                                </div>
                            </div>
                        ))}
                    </Card>
                )}
                {ledger?.last_page > 1 && (
                    <div className="mt-3 flex items-center justify-between text-sm font-semibold">
                        {ledger.prev_page_url ? <TextAction href={ledger.prev_page_url}>{__('client_rewards.referrals.previous')}</TextAction> : <span />}
                        <span className="text-[#1d1d1f]/50" dir="ltr">{ledger.current_page} / {ledger.last_page}</span>
                        {ledger.next_page_url ? <TextAction href={ledger.next_page_url}>{__('client_rewards.referrals.next')}</TextAction> : <span />}
                    </div>
                )}
            </Section>

            {/* Levels */}
            {tiers.length > 0 && (
                <Section title={__('client_rewards.loyalty.levels')}>
                    <p className="-mt-1 mb-3 text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__('client_rewards.loyalty.levels_hint')}</p>
                    <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                        {tiers.map((tier) => {
                            const isCurrent = summary?.current_tier?.id === tier.id;
                            const unlocked = (summary?.lifetime_points || 0) >= tier.min_lifetime_points;
                            const perks = tier.perks_payload?.perks || [];
                            return (
                                <div key={tier.id} className={cn('flex items-start gap-3 px-4 py-3 sm:px-5', isCurrent && 'bg-[#0071e3]/[0.06]', !unlocked && 'opacity-60')}>
                                    <img src={`/images/tiers/${(tier.slug || tier.name).toLowerCase()}.svg`} alt="" className="h-10 w-10 shrink-0" loading="lazy" />
                                    <div className="min-w-0 flex-1">
                                        <p className="font-semibold">
                                            {tierName(tier)}
                                            {isCurrent && <span className="ms-2 text-sm font-semibold text-[#0071e3]">· {__('client_rewards.loyalty.your_level')}</span>}
                                        </p>
                                        <p className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                            {tier.min_lifetime_points > 0
                                                ? __('client_rewards.loyalty.from_points', { points: tier.min_lifetime_points.toLocaleString('en-US') })
                                                : __('client_rewards.loyalty.base_level')}
                                            {Number(tier.discount_percentage) > 0 ? ` · ${__('client_rewards.loyalty.discount', { percent: Number(tier.discount_percentage) })}` : ''}
                                        </p>
                                        {perks.length > 0 && (
                                            <ul className="mt-1 space-y-0.5 text-xs text-[#1d1d1f]/60 dark:text-zinc-400">
                                                {perks.map((perk, i) => <li key={i} className="flex gap-1.5"><Check className="mt-0.5 h-3 w-3 shrink-0 text-emerald-500" />{perk}</li>)}
                                            </ul>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </Card>
                </Section>
            )}

            {/* How to earn */}
            <Section title={__('client_rewards.loyalty.how_to_earn')}>
                <Card className="divide-y divide-black/5 !p-0 dark:divide-white/10">
                    {ways.map(({ icon: Icon, title, desc }) => (
                        <div key={title} className="flex items-start gap-3 px-4 py-3 sm:px-5">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0071e3]/10 text-[#0071e3]"><Icon className="h-4 w-4" /></span>
                            <div className="min-w-0">
                                <p className="font-semibold">{__(title)}</p>
                                <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">{__(desc)}</p>
                            </div>
                        </div>
                    ))}
                </Card>
            </Section>

            {/* Invite friends */}
            {referral?.url && (
                <Section title={__('client_rewards.loyalty.invite')} action={<TextAction href="/referrals">{__('client_rewards.referrals.tab_people')}</TextAction>}>
                    <Card className="space-y-3">
                        <p className="text-sm text-[#1d1d1f]/60 dark:text-zinc-400">
                            {__('client_rewards.loyalty.invite_hint', { count: referral.total_users || 0, points: (referral.points_earned || 0).toLocaleString('en-US') })}
                        </p>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <input readOnly value={referral.url} dir="ltr" aria-label={__('client_rewards.referrals.your_link')}
                                className="w-full rounded-xl border border-black/10 bg-[#f5f5f7] px-3 py-2.5 font-mono text-sm dark:border-white/10 dark:bg-zinc-800" />
                            <button type="button" onClick={copyLink}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-[#1d1d1f] px-5 text-sm font-semibold text-white dark:bg-white dark:text-black">
                                {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                {copied ? __('client_rewards.loyalty.copied') : __('client_rewards.loyalty.copy')}
                            </button>
                        </div>
                    </Card>
                </Section>
            )}
        </ClientPage>
    );
}
