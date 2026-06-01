import React from 'react';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import {
    Receipt, Wallet, ShoppingBag, FileText, CheckSquare,
    Calendar, ArrowUpRight, Users, Package, Zap, Activity,
} from 'lucide-react';

// ── Types ──────────────────────────────────────────────────────────────────────

export interface ActivityEventItem {
    id: number;
    user_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    event: string;
    description: string;
    properties: Record<string, any> | null;
    workspace: string | null;
    created_at: string;
    icon: string;
    color: string;
    user?: {
        id: number;
        name: string;
        avatar?: string | null;
    } | null;
}

export interface ActivityFeedProps {
    items: ActivityEventItem[];
    className?: string;
    showWorkspace?: boolean;
}

// ── Icon & Color Maps ─────────────────────────────────────────────────────────

const ICON_MAP: Record<string, React.ElementType> = {
    receipt:          Receipt,
    wallet:           Wallet,
    'shopping-bag':   ShoppingBag,
    'file-text':      FileText,
    'check-square':   CheckSquare,
    calendar:         Calendar,
    'arrow-up-right': ArrowUpRight,
    users:            Users,
    package:          Package,
    zap:              Zap,
    activity:         Activity,
};

const COLOR_MAP: Record<string, string> = {
    emerald: 'bg-emerald-500',
    blue:    'bg-blue-500',
    indigo:  'bg-indigo-500',
    violet:  'bg-violet-500',
    green:   'bg-green-500',
    cyan:    'bg-cyan-500',
    amber:   'bg-amber-500',
    pink:    'bg-pink-500',
    orange:  'bg-orange-500',
    purple:  'bg-purple-500',
    slate:   'bg-slate-400',
};

const WORKSPACE_COLORS: Record<string, string> = {
    erp:         'text-blue-600 bg-blue-50',
    marketplace: 'text-indigo-600 bg-indigo-50',
    freelance:   'text-violet-600 bg-violet-50',
    booking:     'text-cyan-600 bg-cyan-50',
    system:      'text-slate-600 bg-slate-100',
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function timeAgo(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);

    if (diffSec < 60)   return 'just now';
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
    if (diffSec < 86400)return `${Math.floor(diffSec / 3600)}h ago`;
    if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function UserAvatar({ user }: { user: ActivityEventItem['user'] }) {
    if (!user) {
        return (
            <div className="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                <span className="text-[9px] font-bold text-slate-500">S</span>
            </div>
        );
    }
    return user.avatar ? (
        <img src={user.avatar} alt={user.name} className="w-6 h-6 rounded-full object-cover shrink-0" />
    ) : (
        <div className="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
            <span className="text-[9px] font-bold text-indigo-700">{user.name.charAt(0).toUpperCase()}</span>
        </div>
    );
}

// ── Main Component ────────────────────────────────────────────────────────────

export function ActivityFeed({ items, className, showWorkspace = false }: ActivityFeedProps) {
    if (!items || items.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-10 text-slate-400">
                <Activity className="w-8 h-8 mb-3 opacity-40" />
                <p className="text-sm font-medium">{__('general.no_activity_yet')}</p>
                <p className="text-xs mt-1">{__('general.actions_across_the_platform_will_appear_here')}</p>
            </div>
        );
    }

    return (
        <div className={cn('space-y-0', className)}>
            {items.map((item, idx) => {
                const Icon = ICON_MAP[item.icon] ?? Activity;
                const dotColor = COLOR_MAP[item.color] ?? 'bg-slate-400';
                const isLast = idx === items.length - 1;

                return (
                    <div key={item.id} className="flex gap-4 group">
                        {/* Timeline spine + dot */}
                        <div className="flex flex-col items-center shrink-0">
                            <div className={cn(
                                'w-8 h-8 rounded-full flex items-center justify-center z-10 shrink-0 shadow-sm ring-2 ring-white',
                                dotColor
                            )}>
                                <Icon className="w-3.5 h-3.5 text-white" />
                            </div>
                            {!isLast && <div className="w-[1px] flex-1 bg-slate-100 mt-1 mb-1 min-h-[20px]" />}
                        </div>

                        {/* Content */}
                        <div className={cn('flex-1 min-w-0 pb-5', isLast && 'pb-0')}>
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-start gap-2 flex-1 min-w-0">
                                    <UserAvatar user={item.user} />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm text-slate-800 leading-snug">
                                            {item.user && (
                                                <span className="font-semibold text-slate-900">{item.user.name} </span>
                                            )}
                                            {item.description}
                                        </p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="text-[11px] text-slate-400 font-medium">
                                                {timeAgo(item.created_at)}
                                            </span>
                                            {showWorkspace && item.workspace && (
                                                <span className={cn(
                                                    'text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded',
                                                    WORKSPACE_COLORS[item.workspace] ?? 'text-slate-600 bg-slate-100'
                                                )}>
                                                    {item.workspace}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// Legacy export for backward compatibility
export default ActivityFeed;
export type { ActivityEventItem as ActivityItem };
