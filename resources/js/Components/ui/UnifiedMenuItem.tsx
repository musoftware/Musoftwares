import React from 'react';
import SafeLink from '@/Components/SafeLink';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { LucideIcon } from 'lucide-react';

export interface UnifiedMenuItemProps {
    href: string;
    icon: LucideIcon;
    title: string;
    description?: string;
    isActive?: boolean;
    badge?: React.ReactNode;
    color?: 'blue' | 'emerald' | 'amber' | 'purple' | 'rose';
    onClick?: () => void;
}

export function UnifiedMenuItem({
    href,
    icon: Icon,
    title,
    description,
    isActive = false,
    badge,
    color = 'blue',
    onClick,
}: UnifiedMenuItemProps) {
    const colorClasses = {
        blue: 'text-[#0071e3] dark:text-[#3898ec] group-hover:bg-[#0071e3] group-hover:text-white',
        emerald: 'text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white',
        amber: 'text-amber-600 dark:text-amber-400 group-hover:bg-amber-500 group-hover:text-white',
        purple: 'text-purple-600 dark:text-purple-400 group-hover:bg-purple-600 group-hover:text-white',
        rose: 'text-rose-500 dark:text-rose-400 group-hover:bg-rose-500 group-hover:text-white',
    }[color];

    return (
        <DropdownMenuItem
            className={cn(
                "p-0 outline-none border-0 transition-colors duration-150 cursor-pointer rounded-[14px] group",
                isActive
                    ? "bg-[#f5f5f7] dark:bg-zinc-800/80"
                    : "hover:bg-[#f5f5f7] dark:hover:bg-zinc-800/60"
            )}
            render={
                <SafeLink
                    href={href}
                    aria-current={isActive ? 'page' : undefined}
                    className="flex items-start gap-3 p-2.5 w-full"
                    onClick={onClick}
                />
            }
        >
            <div
                className={cn(
                    "w-8 h-8 rounded-xl bg-[#f5f5f7] dark:bg-zinc-800/90 border border-black/5 dark:border-white/10 flex items-center justify-center shrink-0 transition-all",
                    colorClasses
                )}
            >
                <Icon className="w-4 h-4" />
            </div>
            <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-1">
                    <p className="text-xs font-bold text-[#1d1d1f] dark:text-zinc-100 font-sans truncate">{title}</p>
                    {badge}
                </div>
                {description && (
                    <p className="text-[11px] text-[#1d1d1f]/60 dark:text-zinc-400 font-sans leading-tight mt-0.5 truncate">
                        {description}
                    </p>
                )}
            </div>
        </DropdownMenuItem>
    );
}

export default UnifiedMenuItem;
