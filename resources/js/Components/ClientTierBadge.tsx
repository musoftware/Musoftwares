import React, { useState } from 'react';
import { Crown, Sparkles, Shield, Gem } from 'lucide-react';

export interface TierConfig {
    title: string;
    subtitle: string;
    badge: string;
    badgeStyle: string;
    accentColor: string;
    icon: React.ComponentType<{ className?: string }>;
}

export const getTierConfig = (tier?: string): TierConfig => {
    const cleanTier = (tier || 'bronze').toLowerCase().trim();

    if (cleanTier === 'obsidian' || cleanTier === 'apex' || cleanTier === 'crown') {
        return {
            title: 'Obsidian Imperial VIP',
            subtitle: 'Direct CTO Line • Dedicated Engineering Squad • Custom Architecture',
            badge: '/images/tiers/obsidian.png',
            badgeStyle: 'bg-gradient-to-r from-[#18181B] via-[#3B0764] to-[#18181B] text-[#FEF08A] font-bold shadow-xs border border-[#F59E0B]/50',
            accentColor: 'text-purple-400',
            icon: Crown,
        };
    }

    if (cleanTier === 'diamond') {
        return {
            title: 'Diamond Elite Partner',
            subtitle: '15-Min Guaranteed Engineering SLA • Comprehensive Code Audits',
            badge: '/images/tiers/diamond.png',
            badgeStyle: 'bg-gradient-to-r from-cyan-600 via-teal-500 to-sky-600 text-white font-bold shadow-xs border border-cyan-300/60',
            accentColor: 'text-cyan-400',
            icon: Crown,
        };
    }

    if (cleanTier === 'ruby') {
        return {
            title: 'Ruby Prestige Enterprise',
            subtitle: 'Dedicated Senior Architect • 2-Hour SLA • Complimentary Security Scans',
            badge: '/images/tiers/ruby.png',
            badgeStyle: 'bg-gradient-to-r from-rose-600 via-red-600 to-pink-600 text-white font-bold shadow-xs border border-rose-300/60',
            accentColor: 'text-rose-400',
            icon: Gem,
        };
    }

    if (cleanTier === 'platinum' || cleanTier === 'enterprise') {
        return {
            title: 'Platinum VIP Tier',
            subtitle: 'Executive Dedicated Engineering • Zero-Queue VIP SLA',
            badge: '/images/tiers/platinum.png',
            badgeStyle: 'bg-gradient-to-r from-sky-500 via-indigo-500 to-purple-600 text-white font-semibold shadow-xs border border-sky-300/40',
            accentColor: 'text-sky-400',
            icon: Crown,
        };
    }

    if (cleanTier === 'emerald') {
        return {
            title: 'Emerald Growth Partner',
            subtitle: 'Priority Dispatch Routing • Architecture Sync Calls',
            badge: '/images/tiers/emerald.png',
            badgeStyle: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 text-white font-bold shadow-xs border border-emerald-300/60',
            accentColor: 'text-emerald-400',
            icon: Gem,
        };
    }

    if (cleanTier === 'gold') {
        return {
            title: 'Gold Tier Partner',
            subtitle: 'Priority Queue Routing • Dedicated Technical Lead',
            badge: '/images/tiers/gold.png',
            badgeStyle: 'bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-slate-950 font-bold shadow-xs border border-amber-300/60',
            accentColor: 'text-amber-400',
            icon: Crown,
        };
    }

    if (cleanTier === 'silver' || cleanTier === 'pro') {
        return {
            title: 'Silver Tier Client',
            subtitle: 'Accelerated Ticket Dispatch • Regular Milestone Audits',
            badge: '/images/tiers/silver.png',
            badgeStyle: 'bg-gradient-to-r from-slate-200 via-zinc-200 to-slate-300 text-slate-900 dark:from-slate-800 dark:via-zinc-700 dark:to-slate-800 dark:text-slate-100 font-semibold shadow-xs border border-slate-300 dark:border-slate-600/40',
            accentColor: 'text-slate-400',
            icon: Sparkles,
        };
    }

    return {
        title: 'Bronze Tier Client',
        subtitle: 'Automated Self-Service Studio • Earn Points with Every Milestone',
        badge: '/images/tiers/bronze.png',
        badgeStyle: 'bg-gradient-to-r from-[#7D320B] via-[#B25324] to-[#D9733E] text-white font-semibold shadow-xs border border-[#FFA875]/50',
        accentColor: 'text-[#B25324] dark:text-[#FFA875]',
        icon: Shield,
    };
};

export interface ClientTierBadgeProps {
    tier?: string;
    title?: string;
    size?: 'xs' | 'sm' | 'md';
    showIcon?: boolean;
    points?: number;
    className?: string;
}

export const ClientTierBadge: React.FC<ClientTierBadgeProps> = ({
    tier = 'bronze',
    title,
    size = 'xs',
    showIcon = true,
    points,
    className = '',
}) => {
    const [imgError, setImgError] = useState(false);
    const config = getTierConfig(tier);
    const displayTitle = title || config.title;
    const FallbackIcon = config.icon;

    const sizeClasses = {
        xs: 'px-2 py-0.5 text-[9px]',
        sm: 'px-2.5 py-0.5 text-[10px]',
        md: 'px-3 py-1 text-xs',
    }[size];

    const iconSizeClasses = {
        xs: 'w-3 h-3',
        sm: 'w-3.5 h-3.5',
        md: 'w-4 h-4',
    }[size];

    return (
        <span
            title={config.subtitle}
            className={`inline-flex items-center gap-1.5 rounded-full font-mono tracking-wider uppercase shrink-0 transition-transform select-none ${sizeClasses} ${config.badgeStyle} ${className}`}
        >
            {showIcon && !imgError && (
                <img
                    src={config.badge}
                    alt=""
                    className={`${iconSizeClasses} object-contain shrink-0 drop-shadow-xs`}
                    onError={() => setImgError(true)}
                />
            )}
            {showIcon && imgError && (
                <FallbackIcon className={`${iconSizeClasses} shrink-0`} />
            )}
            <span className="truncate">{displayTitle}</span>
            {typeof points === 'number' && (
                <span className="opacity-90 font-bold ms-0.5">• {points.toLocaleString()} PTS</span>
            )}
        </span>
    );
};

export default ClientTierBadge;
