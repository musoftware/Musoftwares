import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageShell } from '@/Components/ui/PageShell';
import { PageHeroHeader } from '@/Components/ui/PageHeroHeader';
import { ContentCard } from '@/Components/ui/ContentCard';
import { BentoStatCard } from '@/Components/ui/BentoStatCard';
import { Button } from '@/Components/ui/button';
import { 
    Award, Crown, Sparkles, Shield, Coins, ArrowUpRight, 
    Check, Copy, Clock, ArrowRight, Zap, FileText, 
    MessageSquare, Users, Gift, CheckCircle2, ChevronRight 
} from 'lucide-react';
import { formatMoney } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import axios from 'axios';

interface LoyaltyTier {
    id: number;
    name: string;
    slug?: string;
    discount_percentage?: number | string;
    ticket_priority_level?: string;
    badge_color?: string;
    badge_image?: string;
    badge_svg?: string;
    min_lifetime_points: number;
    multiplier?: number;
    perks_payload?: {
        perks?: string[];
        badge_style?: string;
    };
    color_hex?: string;
    badge_icon?: string;
}

interface LedgerEntry {
    id: number;
    event_type: string;
    title: string;
    points: number;
    balance_after: number;
    channel: string;
    reference_type?: string;
    reference_id?: number;
    metadata?: any;
    date: string;
    diff_for_humans: string;
}

interface RewardItem {
    id: number;
    name: string;
    description: string;
    reward_type: string;
    points_cost: number;
    discount_value: number;
    discount_type: string;
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
        profile_completion: number;
        recent_transactions: any[];
    };
    ledger: {
        data: LedgerEntry[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    tiers: LoyaltyTier[];
    rewards: RewardItem[];
    referral: {
        code?: string;
        slug?: string;
        url: string;
        total_users: number;
        points_earned: number;
    };
    pointsConversionRate: number;
}

export default function LoyaltyIndex({
    summary,
    ledger,
    tiers = [],
    rewards = [],
    referral,
    pointsConversionRate = 1 / 30,
}: LoyaltyPageProps) {
    const [currentBalance, setCurrentBalance] = useState<number>(summary?.balance || 0);
    const [redeemingId, setRedeemingId] = useState<number | null>(null);
    const [redeemSuccessMsg, setRedeemSuccessMsg] = useState<string | null>(null);
    const [redeemErrorMsg, setRedeemErrorMsg] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const [activeLedgerTab, setActiveLedgerTab] = useState<'all' | 'earned' | 'spent'>('all');

    const handleCopyReferral = () => {
        if (!referral?.url) return;
        navigator.clipboard.writeText(referral.url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    const handleRedeem = async (reward: RewardItem) => {
        if (currentBalance < reward.points_cost) {
            setRedeemErrorMsg(__('loyalty.insufficient_points') || 'Insufficient points balance.');
            setTimeout(() => setRedeemErrorMsg(null), 4000);
            return;
        }

        try {
            setRedeemingId(reward.id);
            setRedeemErrorMsg(null);
            const res = await axios.post(`/api/portal/loyalty/rewards/${reward.id}/redeem`);
            if (res.data?.status === 'success') {
                setCurrentBalance(res.data.data.new_points_balance);
                setRedeemSuccessMsg(`Successfully redeemed "${reward.name}"! Your account has been credited.`);
                setTimeout(() => setRedeemSuccessMsg(null), 5000);
            }
        } catch (err: any) {
            setRedeemErrorMsg(err?.response?.data?.message || 'Failed to redeem reward.');
            setTimeout(() => setRedeemErrorMsg(null), 4000);
        } finally {
            setRedeemingId(null);
        }
    };

    const filteredLedger = (ledger?.data || []).filter((item) => {
        if (activeLedgerTab === 'earned') return item.points > 0;
        if (activeLedgerTab === 'spent') return item.points < 0;
        return true;
    });

    const currentTierName = summary?.current_tier?.name || (summary?.tier ? summary.tier.toUpperCase() : 'STANDARD');
    const cashValue = +(currentBalance * pointsConversionRate).toFixed(2);

    return (
        <AuthenticatedLayout>
            <Head title={__('loyalty.loyalty_hub') || 'Loyalty & Points Hub'} />

            <div className="w-full">
                {/* Hero Header */}
                <PageHeroHeader
                    badge={__('loyalty.loyalty_hub') || 'Loyalty & Points'}
                    title={__('loyalty.loyalty_hub') || 'Loyalty & Points Hub'}
                    description={__('loyalty.loyalty_tagline') || 'Complete transparency. Earn rewards on every interaction and early invoice settlement.'}
                    actions={
                        <div className="flex items-center gap-3 shrink-0">
                            <Link href="/dashboard">
                                <Button variant="outline" size="sm" className="rounded-full text-xs font-semibold">
                                    Dashboard
                                </Button>
                            </Link>
                            <a href="#rewards-catalog">
                                <Button size="sm" className="bg-[#0071e3] hover:bg-[#0077ed] text-white rounded-full text-xs font-semibold gap-1.5 shadow-sm">
                                    <Gift className="w-3.5 h-3.5" />
                                    <span>{__('loyalty.rewards_catalog') || 'Rewards'}</span>
                                </Button>
                            </a>
                        </div>
                    }
                />

                <PageShell maxWidth="7xl" className="space-y-10">
                    {/* Status Messages */}
                    {redeemSuccessMsg && (
                        <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 text-xs flex items-center gap-3">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>{redeemSuccessMsg}</span>
                        </div>
                    )}
                    {redeemErrorMsg && (
                        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-xs flex items-center gap-3">
                            <Shield className="w-4 h-4 text-rose-600 shrink-0" />
                            <span>{redeemErrorMsg}</span>
                        </div>
                    )}

                    {/* 1. Core Points & Tier Metrics Row */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <BentoStatCard
                            label={__('loyalty.available_points') || 'Available Points'}
                            value={`${currentBalance.toLocaleString()} PTS`}
                            description={`≈ ${cashValue} in direct invoice deductions`}
                            icon={Coins}
                            accentColor="blue"
                        />
                        <BentoStatCard
                            label={__('loyalty.current_tier') || 'Current Tier'}
                            value={currentTierName}
                            description={
                                summary?.next_tier
                                    ? `${summary.points_to_next_tier} PTS to ${summary.next_tier.name}`
                                    : 'Highest tier attained'
                            }
                            icon={
                                <img
                                    src={`/images/tiers/${(summary?.current_tier?.slug || summary?.tier || 'bronze').toLowerCase()}.png`}
                                    alt={currentTierName}
                                    className="w-9 h-9 object-contain drop-shadow-md"
                                    onError={(e) => {
                                        e.currentTarget.src = `/images/tiers/${(summary?.current_tier?.slug || summary?.tier || 'bronze').toLowerCase()}.svg`;
                                    }}
                                />
                            }
                            accentColor="amber"
                        />
                        <BentoStatCard
                            label={__('loyalty.lifetime_points') || 'Lifetime Points'}
                            value={`${(summary?.lifetime_points || 0).toLocaleString()} PTS`}
                            description="All-time earned points counter"
                            icon={Award}
                            accentColor="emerald"
                        />
                        <BentoStatCard
                            label={__('loyalty.referral_points_earned') || 'Referral Points'}
                            value={`${(referral?.points_earned || 0).toLocaleString()} PTS`}
                            description={`${referral?.total_users || 0} colleagues introduced`}
                            icon={Users}
                            accentColor="purple"
                        />
                    </div>

                    {/* 2. Tier Progression Track (Visual Ladder) */}
                    <ContentCard
                        title={__('loyalty.tier_ladder') || 'Tier Progression Ladder'}
                        subtitle={
                            summary?.next_tier
                                ? `You are ${summary?.progress_percentage}% toward unlocking ${summary?.next_tier?.name}.`
                                : 'You have reached our highest partnership tier. Thank you for your leadership.'
                        }
                    >
                        <div className="space-y-6 pt-2">
                            {/* Progress bar */}
                            {summary?.next_tier && (
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between text-xs font-mono text-zinc-500 dark:text-zinc-400">
                                        <span className="font-semibold text-zinc-700 dark:text-zinc-300">
                                            {summary?.current_tier?.name || 'Bronze'}
                                        </span>
                                        <span className="font-bold text-[#0071e3]">{summary?.progress_percentage}%</span>
                                        <span className="font-semibold text-zinc-700 dark:text-zinc-300">
                                            {summary?.next_tier?.name}
                                        </span>
                                    </div>
                                    <div className="w-full bg-zinc-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden">
                                        <div 
                                            className="bg-gradient-to-r from-blue-500 to-sky-400 h-full rounded-full transition-all duration-700 shadow-[0_0_10px_rgba(56,189,248,0.4)]" 
                                            style={{ width: `${Math.min(100, Math.max(0, summary?.progress_percentage || 0))}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Tiers Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                {tiers.map((tierItem) => {
                                    const isCurrent = summary?.current_tier?.id === tierItem.id;
                                    const isUnlocked = (summary?.lifetime_points || 0) >= tierItem.min_lifetime_points;
                                    const perks = tierItem.perks_payload?.perks || [];
                                    const tierSlug = (tierItem.slug || tierItem.name).toLowerCase();
                                    const badgeSrc = `/images/tiers/${tierSlug}.png`;
                                    const badgeSvgFallback = `/images/tiers/${tierSlug}.svg`;

                                    return (
                                        <div
                                            key={tierItem.id}
                                            className={`relative p-5 rounded-2xl border transition-all flex flex-col justify-between ${
                                                isCurrent
                                                    ? 'bg-blue-50/60 dark:bg-blue-950/20 border-[#0071e3]/50 shadow-md ring-1 ring-[#0071e3]/30'
                                                    : isUnlocked
                                                    ? 'bg-zinc-50/60 dark:bg-zinc-900/50 border-black/5 dark:border-white/5'
                                                    : 'bg-white/40 dark:bg-zinc-950/40 border-black/5 dark:border-white/5 opacity-75'
                                            }`}
                                        >
                                            {isCurrent && (
                                                <div className="absolute -top-3 end-4">
                                                    <span className="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-[#0071e3] text-white shadow-sm">
                                                        CURRENT TIER
                                                    </span>
                                                </div>
                                            )}

                                            <div>
                                                {/* 3D Tier Badge Showcase */}
                                                <div className="flex flex-col items-center text-center p-3 mb-3 bg-gradient-to-b from-black/[0.02] to-transparent dark:from-white/[0.02] rounded-xl">
                                                    <img
                                                        src={badgeSrc}
                                                        alt={tierItem.name}
                                                        className="w-20 h-20 object-contain drop-shadow-md transition-transform hover:scale-105"
                                                        onError={(e) => {
                                                            e.currentTarget.src = badgeSvgFallback;
                                                        }}
                                                    />
                                                    <h4 className="font-bold text-base text-[#1d1d1f] dark:text-white mt-2 font-sans">
                                                        {tierItem.name}
                                                    </h4>
                                                    <span className="text-[11px] font-mono text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                        {tierItem.min_lifetime_points > 0
                                                            ? `${tierItem.min_lifetime_points.toLocaleString()} PTS Threshold`
                                                            : 'Base Entry Level'}
                                                    </span>
                                                    {Number(tierItem.discount_percentage) > 0 && (
                                                        <span className="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                                            {tierItem.discount_percentage}% Invoice Deduction
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="pt-2 border-t border-black/5 dark:border-white/5">
                                                    <div className="text-[10px] font-mono uppercase text-zinc-400 dark:text-zinc-500 mb-2 font-semibold">
                                                        Tier Privileges
                                                    </div>
                                                    <ul className="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                                        {perks.map((perk, pIdx) => (
                                                            <li key={pIdx} className="flex items-start gap-2">
                                                                <Check className="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                                                <span className="leading-tight">{perk}</span>
                                                            </li>
                                                        ))}
                                                        {perks.length === 0 && (
                                                            <>
                                                                <li className="flex items-start gap-2">
                                                                    <Check className="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                                                    <span className="leading-tight">Instant Support Ticket Priority</span>
                                                                </li>
                                                                <li className="flex items-start gap-2">
                                                                    <Check className="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                                                    <span className="leading-tight">Transparent Audit Ledger Tracking</span>
                                                                </li>
                                                            </>
                                                        )}
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </ContentCard>

                    {/* 3. Transparent Points Ledger (The Core Trust Feature) */}
                    <ContentCard
                        title={__('loyalty.points_ledger') || 'Points Audit Ledger'}
                        subtitle={__('loyalty.points_ledger_subtitle') || 'Every single point transaction is recorded below with complete transparency.'}
                        action={
                            <div className="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl text-xs font-medium">
                                <button
                                    type="button"
                                    onClick={() => setActiveLedgerTab('all')}
                                    className={`px-3 py-1 rounded-lg transition-all ${
                                        activeLedgerTab === 'all'
                                            ? 'bg-white dark:bg-zinc-900 text-[#1d1d1f] dark:text-white shadow-xs font-semibold'
                                            : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'
                                    }`}
                                >
                                    {__('loyalty.filter_all') || 'All'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveLedgerTab('earned')}
                                    className={`px-3 py-1 rounded-lg transition-all ${
                                        activeLedgerTab === 'earned'
                                            ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-semibold'
                                            : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'
                                    }`}
                                >
                                    {__('loyalty.filter_earned') || 'Earned (+)'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveLedgerTab('spent')}
                                    className={`px-3 py-1 rounded-lg transition-all ${
                                        activeLedgerTab === 'spent'
                                            ? 'bg-white dark:bg-zinc-900 text-rose-600 dark:text-rose-400 shadow-xs font-semibold'
                                            : 'text-zinc-500 dark:text-zinc-400 hover:text-black dark:hover:text-white'
                                    }`}
                                >
                                    {__('loyalty.filter_spent') || 'Redeemed (-)'}
                                </button>
                            </div>
                        }
                    >
                        <div className="overflow-x-auto -mx-6 px-6">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr className="border-b border-black/5 dark:border-white/5 text-zinc-500 dark:text-zinc-400 font-mono text-[11px] uppercase tracking-wider">
                                        <th className="py-3 px-3 font-semibold">{__('loyalty.transaction_date') || 'Date (Cairo)'}</th>
                                        <th className="py-3 px-3 font-semibold">{__('loyalty.event_description') || 'Activity'}</th>
                                        <th className="py-3 px-3 font-semibold">{__('loyalty.channel') || 'Channel'}</th>
                                        <th className="py-3 px-3 font-semibold text-right">{__('loyalty.points_delta') || 'Points'}</th>
                                        <th className="py-3 px-3 font-semibold text-right">{__('loyalty.balance_after') || 'Balance After'}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-black/5 dark:divide-white/5">
                                    {filteredLedger.map((item) => {
                                        const isPositive = item.points > 0;
                                        return (
                                            <tr key={item.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                                <td className="py-3.5 px-3 font-mono text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                                    <div>{item.date}</div>
                                                    <div className="text-[10px] text-zinc-400">{item.diff_for_humans}</div>
                                                </td>
                                                <td className="py-3.5 px-3">
                                                    <div className="font-semibold text-[#1d1d1f] dark:text-white">
                                                        {item.title}
                                                    </div>
                                                    {item.metadata?.reason && (
                                                        <div className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                            Note: {item.metadata.reason}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-3">
                                                    <span className="inline-flex px-2 py-0.5 rounded-full text-[10px] font-mono uppercase bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                                        {item.channel}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-3 text-right font-mono font-bold whitespace-nowrap">
                                                    <span className={isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-700 dark:text-zinc-300'}>
                                                        {isPositive ? `+${item.points}` : item.points} PTS
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-3 text-right font-mono font-semibold text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                                    {item.balance_after.toLocaleString()} PTS
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {filteredLedger.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                                {__('loyalty.no_transactions') || 'No points activity recorded yet.'}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {ledger?.last_page > 1 && (
                            <div className="flex items-center justify-between pt-4 mt-4 border-t border-black/5 dark:border-white/5 text-xs text-zinc-500">
                                <span>Page {ledger.current_page} of {ledger.last_page}</span>
                                <div className="flex items-center gap-2">
                                    {ledger.links?.map((link, idx) => {
                                        if (!link.url) return null;
                                        return (
                                            <Link
                                                key={idx}
                                                href={link.url}
                                                className={`px-3 py-1 rounded-lg border ${
                                                    link.active
                                                        ? 'bg-[#0071e3] text-white border-[#0071e3] font-semibold'
                                                        : 'border-black/5 dark:border-white/10 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </ContentCard>

                    {/* 4. Rewards Catalog & Instant Redemption */}
                    <div id="rewards-catalog">
                        <ContentCard
                            title={__('loyalty.rewards_catalog') || 'Rewards & Redemptions'}
                            subtitle={__('loyalty.rewards_catalog_subtitle') || 'Exchange your available points for real billing credits and premium SLA hours.'}
                        >
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 pt-2">
                                {rewards.map((reward) => {
                                    const canAfford = currentBalance >= reward.points_cost;
                                    const isRedeeming = redeemingId === reward.id;

                                    return (
                                        <div
                                            key={reward.id}
                                            className="p-5 rounded-2xl border border-black/5 dark:border-white/10 bg-white dark:bg-zinc-900 flex flex-col justify-between hover:shadow-md transition-all"
                                        >
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-blue-50 dark:bg-blue-950/50 text-[#0071e3] dark:text-[#3898ec]">
                                                        {reward.points_cost.toLocaleString()} PTS
                                                    </span>
                                                    <span className="text-[11px] font-mono text-zinc-400 uppercase">
                                                        {reward.reward_type.replace('_', ' ')}
                                                    </span>
                                                </div>
                                                <h4 className="font-semibold text-sm text-[#1d1d1f] dark:text-white pt-1">
                                                    {reward.name}
                                                </h4>
                                                <p className="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                                    {reward.description}
                                                </p>
                                            </div>

                                            <div className="pt-5 mt-4 border-t border-black/5 dark:border-white/5 flex items-center justify-between">
                                                <span className="text-[11px] font-mono text-zinc-500">
                                                    {canAfford ? 'Eligible to redeem' : `${reward.points_cost - currentBalance} more PTS needed`}
                                                </span>
                                                <Button
                                                    size="sm"
                                                    disabled={!canAfford || isRedeeming}
                                                    onClick={() => handleRedeem(reward)}
                                                    className={`rounded-xl text-xs font-semibold ${
                                                        canAfford
                                                            ? 'bg-[#1d1d1f] hover:bg-black text-white dark:bg-white dark:text-black dark:hover:bg-zinc-200'
                                                            : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500 cursor-not-allowed'
                                                    }`}
                                                >
                                                    {isRedeeming ? 'Applying...' : 'Redeem'}
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </ContentCard>
                    </div>

                    {/* 5. How to Earn Points (Transparency Matrix) */}
                    <ContentCard
                        title={__('loyalty.how_to_earn') || 'How Points Are Earned'}
                        subtitle={__('loyalty.how_to_earn_subtitle') || 'Our automated system rewards your proactive engagement automatically.'}
                    >
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 pt-2">
                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
                                    <Clock className="w-4 h-4" />
                                    <span>Early Invoice Settlement</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Pay 7+ days early for a 2x bonus (400 PTS), or 3-6 days early for 1.5x (300 PTS). On-time payments receive 200 PTS.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-[#0071e3] dark:text-[#3898ec] font-semibold text-xs">
                                    <MessageSquare className="w-4 h-4" />
                                    <span>Portal Support Tickets</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Opening support tickets via the client portal awards +15 PTS. Promptly resolved tickets earn an extra +25 PTS.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-amber-600 dark:text-amber-400 font-semibold text-xs">
                                    <Zap className="w-4 h-4" />
                                    <span>Company Profile 100%</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Finalizing your complete corporate credentials and contact profiles awards an immediate one-time +50 PTS reward.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-violet-600 dark:text-violet-400 font-semibold text-xs">
                                    <Users className="w-4 h-4" />
                                    <span>Referral Network</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Earn +100 PTS whenever a colleague registers through your referral link, plus +250 PTS when they pay their first invoice.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-sky-600 dark:text-sky-400 font-semibold text-xs">
                                    <Shield className="w-4 h-4" />
                                    <span>Welcome Bonus</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Every verified client receives a starter balance of +50 PTS immediately upon account creation.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/50 border border-black/5 dark:border-white/5 space-y-2">
                                <div className="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-semibold text-xs">
                                    <Award className="w-4 h-4" />
                                    <span>Human Courtesy Override</span>
                                </div>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Admins can grant courtesy bonus points for patience or milestone achievements, always noted transparently in your ledger.
                                </p>
                            </div>
                        </div>
                    </ContentCard>

                    {/* 6. Referral Engine Card */}
                    <ContentCard
                        title={__('loyalty.referrals_hub') || 'Referral Rewards'}
                        subtitle={__('loyalty.referrals_hub_subtitle') || 'Invite colleagues and partners. Earn points when they join and settle services.'}
                    >
                        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pt-2">
                            <div className="space-y-2 max-w-xl">
                                <p className="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    Share your unique invitation link with partner businesses and software teams. Every registered partner gives you immediate points and priority status.
                                </p>
                                <div className="flex items-center gap-3 text-xs font-mono">
                                    <span className="text-zinc-500">Referred: <strong className="text-zinc-900 dark:text-white font-bold">{referral?.total_users || 0}</strong></span>
                                    <span className="text-zinc-300 dark:text-zinc-700">•</span>
                                    <span className="text-emerald-600 dark:text-emerald-400 font-semibold">Points Earned: +{(referral?.points_earned || 0).toLocaleString()} PTS</span>
                                </div>
                            </div>

                            <div className="flex items-center gap-2 w-full md:w-auto">
                                <input
                                    type="text"
                                    readOnly
                                    value={referral?.url || ''}
                                    className="px-3 py-2 text-xs font-mono rounded-xl border border-black/10 dark:border-white/10 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 w-full sm:w-80 select-all"
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={handleCopyReferral}
                                    className="rounded-xl text-xs font-semibold gap-1.5 shrink-0 bg-[#0071e3] hover:bg-[#0077ed] text-white"
                                >
                                    {copied ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                                    <span>{copied ? 'Copied' : 'Copy'}</span>
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                </PageShell>
            </div>
        </AuthenticatedLayout>
    );
}
