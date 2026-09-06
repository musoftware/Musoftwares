import React from 'react';
import { cn } from '@/lib/utils';

export type BentoStatTone = 'emerald' | 'blue' | 'amber' | 'cyan' | 'purple' | 'rose' | 'slate';

export interface BentoStatCardProps {
    label: React.ReactNode;
    value: React.ReactNode;
    description?: React.ReactNode;
    icon?: React.ElementType | React.ReactNode;
    tone?: BentoStatTone;
    accentColor?: BentoStatTone;
    action?: React.ReactNode;
    className?: string;
    onClick?: () => void;
}

const toneStyles: Record<BentoStatTone, { container: string; icon: string; borderHover: string }> = {
    emerald: {
        container: 'bg-emerald-500/10 dark:bg-emerald-950/30 border-emerald-500/20 text-emerald-600 dark:text-emerald-400',
        icon: 'text-emerald-600 dark:text-emerald-400',
        borderHover: 'hover:border-emerald-500/30',
    },
    blue: {
        container: 'bg-[#0071e3]/10 dark:bg-blue-950/30 border-[#0071e3]/20 text-[#0071e3] dark:text-blue-400',
        icon: 'text-[#0071e3] dark:text-blue-400',
        borderHover: 'hover:border-[#0071e3]/30',
    },
    amber: {
        container: 'bg-amber-500/10 dark:bg-amber-950/30 border-amber-500/20 text-amber-600 dark:text-amber-400',
        icon: 'text-amber-600 dark:text-amber-400',
        borderHover: 'hover:border-amber-500/30',
    },
    cyan: {
        container: 'bg-cyan-500/10 dark:bg-cyan-950/30 border-cyan-500/20 text-cyan-600 dark:text-cyan-400',
        icon: 'text-cyan-600 dark:text-cyan-400',
        borderHover: 'hover:border-cyan-500/30',
    },
    purple: {
        container: 'bg-purple-500/10 dark:bg-purple-950/30 border-purple-500/20 text-purple-600 dark:text-purple-400',
        icon: 'text-purple-600 dark:text-purple-400',
        borderHover: 'hover:border-purple-500/30',
    },
    rose: {
        container: 'bg-rose-500/10 dark:bg-rose-950/30 border-rose-500/20 text-rose-600 dark:text-rose-400',
        icon: 'text-rose-600 dark:text-rose-400',
        borderHover: 'hover:border-rose-500/30',
    },
    slate: {
        container: 'bg-slate-500/10 dark:bg-zinc-800/60 border-slate-500/20 text-slate-600 dark:text-zinc-300',
        icon: 'text-slate-600 dark:text-zinc-300',
        borderHover: 'hover:border-slate-400/30',
    },
};

/**
 * BentoStatCard — Apple-grade bento statistic tile with full dark mode support.
 */
export function BentoStatCard({
    label,
    value,
    description,
    icon,
    tone = 'blue',
    accentColor,
    action,
    className,
    onClick,
}: BentoStatCardProps) {
    const effectiveTone = accentColor || tone;
    const toneConfig = toneStyles[effectiveTone] || toneStyles.blue;

    const renderIcon = () => {
        if (!icon) return null;
        if (React.isValidElement(icon)) {
            return icon;
        }
        const IconComponent = icon as React.ElementType;
        return <IconComponent className={cn('w-7 h-7', toneConfig.icon)} />;
    };

    return (
        <div
            data-slot="bento-stat-card"
            onClick={onClick}
            className={cn(
                'group p-6 sm:p-7 rounded-[24px] shadow-sm transition-all flex items-center justify-between',
                'bg-white dark:bg-zinc-900/80 border border-black/5 dark:border-white/10',
                toneConfig.borderHover,
                'hover:shadow-md',
                onClick && 'cursor-pointer',
                className
            )}
        >
            <div className="space-y-2 min-w-0 flex-1">
                <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-[#1d1d1f]/50 dark:text-zinc-400 block truncate">
                    {label}
                </span>

                <div className="text-3xl sm:text-4xl font-bold text-[#1d1d1f] dark:text-white tracking-tight font-sans">
                    {value}
                </div>

                {description && (
                    <div className="text-xs text-[#1d1d1f]/60 dark:text-zinc-400 leading-normal max-w-sm">
                        {description}
                    </div>
                )}

                {action && <div className="pt-1">{action}</div>}
            </div>

            {icon && (
                <div
                    className={cn(
                        'w-14 h-14 rounded-2xl border flex items-center justify-center shrink-0 ms-4 transition-transform group-hover:scale-105',
                        toneConfig.container
                    )}
                >
                    {renderIcon()}
                </div>
            )}
        </div>
    );
}

export default BentoStatCard;
