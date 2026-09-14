import React from 'react';
import { Crown, Sparkles, Shield, ArrowUpRight, Award, Zap } from 'lucide-react';

interface LoyaltyTierHeaderProps {
    clientName: string;
    tier: string;
    loyaltyPoints: number;
    profileCompletion: number;
    onOpenRewardsModal: () => void;
    onCompleteProfile: () => void;
}

export const LoyaltyTierHeader: React.FC<LoyaltyTierHeaderProps> = ({
    clientName,
    tier = 'standard',
    loyaltyPoints = 0,
    profileCompletion = 25,
    onOpenRewardsModal,
    onCompleteProfile,
}) => {
    const cleanTier = (tier || 'standard').toLowerCase();

    const getTierConfig = () => {
        if (cleanTier === 'enterprise') {
            return {
                title: 'Enterprise Titanium VIP',
                subtitle: 'Direct Core Team Routing • Priority Queue Slot #1 • Continuous Architecture Review',
                icon: Crown,
                badgeStyle: 'bg-gradient-to-r from-amber-200 via-amber-400 to-yellow-500 text-black font-semibold shadow-sm',
                accentColor: 'text-amber-400',
            };
        }
        if (cleanTier === 'pro') {
            return {
                title: 'Pro VIP Tier',
                subtitle: 'Accelerated SLA Queue • 2x Loyalty Yield on Settlements • Senior Engineer Dispatch',
                icon: Sparkles,
                badgeStyle: 'bg-gradient-to-r from-sky-400 via-blue-400 to-indigo-500 text-black font-semibold shadow-sm',
                accentColor: 'text-sky-400',
            };
        }
        return {
            title: 'Standard Tier Client',
            subtitle: 'Automated Self-Service Studio • Complete Actions to Upgrade Automatically',
            icon: Shield,
            badgeStyle: 'bg-zinc-800 text-zinc-300 border border-white/10 font-medium',
            accentColor: 'text-zinc-400',
        };
    };

    const config = getTierConfig();
    const TierIcon = config.icon;

    // Approximate monetary utility of points (100 PTS = $50 equivalent in discounts/retainer)
    const pointsMonetaryValue = Math.round((loyaltyPoints / 100) * 50);

    return (
        <section className="relative overflow-hidden w-full rounded-2xl bg-gradient-to-br from-zinc-900/95 via-[#18181b]/95 to-zinc-950 border border-white/10 p-6 sm:p-7 shadow-2xl backdrop-blur-2xl">
            {/* Ambient Background Glow */}
            <div className="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-white/[0.02] blur-3xl pointer-events-none" />

            <div className="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                {/* Client & Tier Metadata */}
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold tracking-tight text-white font-sans">
                            {clientName || 'Private Client'}
                        </h1>
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono tracking-wider uppercase ${config.badgeStyle}`}>
                            <TierIcon className="w-3.5 h-3.5" />
                            <span>{config.title}</span>
                        </span>
                    </div>
                    <p className="text-xs text-zinc-400 font-normal max-w-xl leading-relaxed">
                        {config.subtitle}
                    </p>
                </div>

                {/* Apple Card Style Points & Reward Currency */}
                <div className="flex items-center gap-4 self-stretch md:self-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-white/5">
                    <div className="text-left md:text-right">
                        <div className="text-[11px] font-mono uppercase tracking-wider text-zinc-400">
                            Available Loyalty Capital
                        </div>
                        <div className="text-2xl font-mono font-bold text-white tabular-nums mt-0.5">
                            {loyaltyPoints.toLocaleString()} <span className="text-xs font-sans font-normal text-zinc-400">PTS</span>
                        </div>
                        {loyaltyPoints > 0 && (
                            <div className="text-[11px] font-mono text-emerald-400/90 mt-0.5">
                                ≈ ${pointsMonetaryValue} in service deductions
                            </div>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={onOpenRewardsModal}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white text-black hover:bg-zinc-200 transition-all duration-200 shadow-md active:scale-98"
                    >
                        <Award className="w-4 h-4 text-black" />
                        <span>Redeem Rewards</span>
                    </button>
                </div>
            </div>

            {/* Profile 100% Incentive Bar */}
            {profileCompletion < 100 && (
                <div className="relative z-10 mt-6 pt-4 border-t border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <span className="text-zinc-400 text-[11px] font-mono">
                            DATA PROFILE: <strong className="text-zinc-200 font-semibold">{profileCompletion}%</strong>
                        </span>
                        <div className="flex-1 sm:w-40 bg-zinc-800/80 h-1.5 rounded-full overflow-hidden">
                            <div
                                className="bg-emerald-400 h-full transition-all duration-700 rounded-full"
                                style={{ width: `${profileCompletion}%` }}
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onCompleteProfile}
                        className="text-xs font-medium text-emerald-400 hover:text-emerald-300 inline-flex items-center gap-1 transition-colors"
                    >
                        <span>Finalize company credentials (+50 PTS)</span>
                        <ArrowUpRight className="w-3.5 h-3.5" />
                    </button>
                </div>
            )}
        </section>
    );
};
