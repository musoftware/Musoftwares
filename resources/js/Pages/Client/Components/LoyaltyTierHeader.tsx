import React from 'react';
import { Link } from '@inertiajs/react';
import { Crown, Sparkles, Shield, ArrowUpRight, Award, Zap, History, Gem } from 'lucide-react';
import { formatMoney } from '@/lib/utils';

interface LoyaltyTierHeaderProps {
    clientName: string;
    tier: string;
    loyaltyPoints: number;
    profileCompletion: number;
    currency?: any;
    pointsToMoneyRate?: number;
    onOpenRewardsModal: () => void;
    onCompleteProfile: () => void;
}

export const LoyaltyTierHeader: React.FC<LoyaltyTierHeaderProps> = ({
    clientName,
    tier = 'standard',
    loyaltyPoints = 0,
    profileCompletion = 25,
    currency,
    pointsToMoneyRate = 1 / 30,
    onOpenRewardsModal,
    onCompleteProfile,
}) => {
    const cleanTier = (tier || 'bronze').toLowerCase();

    const getTierConfig = () => {
        if (cleanTier === 'obsidian' || cleanTier === 'apex' || cleanTier === 'crown') {
            return {
                title: 'Obsidian Imperial VIP',
                subtitle: 'Direct CTO Line • Dedicated Engineering Squad • Custom Architecture • 25% Invoice Deduction',
                icon: Crown,
                badge: '/images/tiers/obsidian.png',
                badgeStyle: 'bg-gradient-to-r from-[#18181B] via-[#3B0764] to-[#18181B] text-[#FEF08A] font-bold shadow-xs border border-[#F59E0B]/50',
                accentColor: 'text-purple-400',
            };
        }
        if (cleanTier === 'diamond') {
            return {
                title: 'Diamond Elite Partner',
                subtitle: '15-Min Guaranteed Engineering SLA • Comprehensive Code Audits • 20% Invoice Deduction',
                icon: Crown,
                badge: '/images/tiers/diamond.png',
                badgeStyle: 'bg-gradient-to-r from-cyan-600 via-teal-500 to-sky-600 text-white font-bold shadow-xs border border-cyan-300/60',
                accentColor: 'text-cyan-400',
            };
        }
        if (cleanTier === 'ruby') {
            return {
                title: 'Ruby Prestige Enterprise',
                subtitle: 'Dedicated Senior Architect • 2-Hour SLA • Complimentary Security Scans • 18% Invoice Deduction',
                icon: Gem,
                badge: '/images/tiers/ruby.png',
                badgeStyle: 'bg-gradient-to-r from-rose-600 via-red-600 to-pink-600 text-white font-bold shadow-xs border border-rose-300/60',
                accentColor: 'text-rose-400',
            };
        }
        if (cleanTier === 'platinum' || cleanTier === 'enterprise') {
            return {
                title: 'Platinum VIP Tier',
                subtitle: 'Executive Dedicated Engineering • Zero-Queue VIP SLA • 15% Invoice Deduction Privilege',
                icon: Crown,
                badge: '/images/tiers/platinum.png',
                badgeStyle: 'bg-gradient-to-r from-sky-500 via-indigo-500 to-purple-600 text-white font-semibold shadow-xs border border-sky-300/40',
                accentColor: 'text-sky-400',
            };
        }
        if (cleanTier === 'emerald') {
            return {
                title: 'Emerald Growth Partner',
                subtitle: 'Priority Dispatch Routing • Architecture Sync Calls • Extended Warranty • 12% Invoice Deduction',
                icon: Gem,
                badge: '/images/tiers/emerald.png',
                badgeStyle: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 text-white font-bold shadow-xs border border-emerald-300/60',
                accentColor: 'text-emerald-400',
            };
        }
        if (cleanTier === 'gold') {
            return {
                title: 'Gold Tier Partner',
                subtitle: 'Priority Queue Routing • 10% Invoice Deduction Privilege • Dedicated Technical Lead',
                icon: Crown,
                badge: '/images/tiers/gold.png',
                badgeStyle: 'bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-slate-950 font-bold shadow-xs border border-amber-300/60',
                accentColor: 'text-amber-400',
            };
        }
        if (cleanTier === 'silver' || cleanTier === 'pro') {
            return {
                title: 'Silver Tier Client',
                subtitle: 'Accelerated Ticket Dispatch • 5% Invoice Deduction Privilege • Regular Milestone Audits',
                icon: Sparkles,
                badge: '/images/tiers/silver.png',
                badgeStyle: 'bg-gradient-to-r from-slate-200 via-zinc-200 to-slate-300 text-slate-900 dark:from-slate-800 dark:via-zinc-700 dark:to-slate-800 dark:text-slate-100 font-semibold shadow-xs border border-slate-300 dark:border-slate-600/40',
                accentColor: 'text-slate-400',
            };
        }
        return {
            title: 'Bronze Tier Client',
            subtitle: 'Automated Self-Service Studio • Earn Points with Every Milestone and Early Settlement',
            icon: Shield,
            badge: '/images/tiers/bronze.png',
            badgeStyle: 'bg-gradient-to-r from-[#7D320B] via-[#B25324] to-[#D9733E] text-white font-semibold shadow-xs border border-[#FFA875]/50',
            accentColor: 'text-[#B25324] dark:text-[#FFA875]',
        };
    };

    const config = getTierConfig();
    const TierIcon = config.icon;

    // Monetary value of points using the rate defined in LoyaltyService::POINTS_TO_CURRENCY_RATE
    const pointsMonetaryValue = +(loyaltyPoints * pointsToMoneyRate).toFixed(2);

    return (
        <section className="relative overflow-hidden w-full rounded-2xl bg-gradient-to-br from-white via-[#fbfbfd] to-[#f5f5f7] dark:from-zinc-900/95 dark:via-[#18181b]/95 dark:to-zinc-950 border border-black/5 dark:border-white/10 p-6 sm:p-7 shadow-sm dark:shadow-2xl backdrop-blur-2xl transition-colors duration-200">
            {/* Ambient Background Glow */}
            <div className="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-blue-500/[0.04] dark:bg-white/[0.02] blur-3xl pointer-events-none" />

            <div className="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                {/* Client & Tier Metadata */}
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <img
                            src={config.badge}
                            alt={config.title}
                            className="w-9 h-9 object-contain drop-shadow-md shrink-0"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                            }}
                        />
                        <h1 className="text-2xl font-bold tracking-tight text-[#1d1d1f] dark:text-white font-sans">
                            {clientName || 'Private Client'}
                        </h1>
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono tracking-wider uppercase ${config.badgeStyle}`}>
                            <img
                                src={config.badge}
                                alt=""
                                className="w-3.5 h-3.5 object-contain shrink-0 drop-shadow-xs"
                                onError={(e) => {
                                    e.currentTarget.style.display = 'none';
                                }}
                            />
                            <span>{config.title}</span>
                        </span>
                    </div>
                    <p className="text-xs text-zinc-600 dark:text-zinc-400 font-normal max-w-xl leading-relaxed">
                        {config.subtitle}
                    </p>
                </div>

                {/* Apple Card Style Points & Reward Currency */}
                <div className="flex items-center gap-4 self-stretch md:self-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-black/5 dark:border-white/5">
                    <div className="text-left md:text-right">
                        <div className="text-[11px] font-mono uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Available Loyalty Capital
                        </div>
                        <div className="text-2xl font-mono font-bold text-[#1d1d1f] dark:text-white tabular-nums mt-0.5">
                            {loyaltyPoints.toLocaleString()} <span className="text-xs font-sans font-normal text-zinc-500 dark:text-zinc-400">PTS</span>
                        </div>
                        {loyaltyPoints > 0 && (
                            <div className="text-[11px] font-mono text-emerald-600 dark:text-emerald-400/90 mt-0.5 font-medium">
                                ≈ {formatMoney(pointsMonetaryValue, currency)} in service deductions
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/loyalty"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold border border-black/10 dark:border-white/10 bg-white/80 dark:bg-zinc-900 text-[#1d1d1f] dark:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all shadow-xs cursor-pointer"
                        >
                            <History className="w-3.5 h-3.5 text-zinc-500" />
                            <span>Ledger & Tiers</span>
                        </Link>
                        <button
                            type="button"
                            onClick={onOpenRewardsModal}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#1d1d1f] hover:bg-black text-white dark:bg-white dark:text-black dark:hover:bg-zinc-200 transition-all duration-200 shadow-sm active:scale-98 cursor-pointer"
                        >
                            <Award className="w-4 h-4 text-white dark:text-black" />
                            <span>Redeem</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Profile 100% Incentive Bar */}
            {profileCompletion < 100 && (
                <div className="relative z-10 mt-6 pt-4 border-t border-black/5 dark:border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px] font-mono">
                            DATA PROFILE: <strong className="text-zinc-900 dark:text-zinc-200 font-semibold">{profileCompletion}%</strong>
                        </span>
                        <div className="flex-1 sm:w-40 bg-zinc-200 dark:bg-zinc-800/80 h-1.5 rounded-full overflow-hidden">
                            <div
                                className="bg-emerald-500 dark:bg-emerald-400 h-full transition-all duration-700 rounded-full"
                                style={{ width: `${profileCompletion}%` }}
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onCompleteProfile}
                        className="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 inline-flex items-center gap-1 transition-colors cursor-pointer"
                    >
                        <span>Finalize company credentials (+50 PTS)</span>
                        <ArrowUpRight className="w-3.5 h-3.5" />
                    </button>
                </div>
            )}
        </section>
    );
};
