import React from 'react';
import {
    Activity, DollarSign, FileText, Users, Briefcase,
    LifeBuoy, ArrowUpRight, CornerDownRight, Key, Wrench,
    CreditCard, MessageSquare, ShieldCheck, Bell,
} from 'lucide-react';
import { cn, formatDateRelative } from '@/lib/utils';
import { __ } from '@/lib/i18n';

export interface ActivityTimelineItem {
    id?: number;
    title?: string;
    description?: string;
    time?: string;
    user?: string;
    event?: string;
    icon?: React.ReactNode;
    color?: string;
    created_at?: string;
}

/** Maps an event/action string to a Lucide icon element */
export function getIconForEvent(event: string): React.ReactNode {
    if (!event) return <Activity className="h-3.5 w-3.5 text-slate-400" />;
    const e = event.toLowerCase();
    if (e.includes('invoice_paid') || e.includes('paid')) return <DollarSign className="h-3.5 w-3.5 text-emerald-500" />;
    if (e.includes('invoice')) return <FileText className="h-3.5 w-3.5 text-indigo-500" />;
    if (e.includes('client')) return <Users className="h-3.5 w-3.5 text-blue-500" />;
    if (e.includes('project')) return <Briefcase className="h-3.5 w-3.5 text-amber-500" />;
    if (e.includes('ticket') || e.includes('support')) return <LifeBuoy className="h-3.5 w-3.5 text-rose-500" />;
    if (e.includes('wallet_credit') || e.includes('deposit') || e.includes('credit')) return <ArrowUpRight className="h-3.5 w-3.5 text-emerald-500" />;
    if (e.includes('wallet_debit') || e.includes('withdrawal') || e.includes('debit')) return <CornerDownRight className="h-3.5 w-3.5 text-rose-500" />;
    if (e.includes('license') || e.includes('key')) return <Key className="h-3.5 w-3.5 text-fuchsia-500" />;
    if (e.includes('tool') || e.includes('plugin')) return <Wrench className="h-3.5 w-3.5 text-fuchsia-500" />;
    if (e.includes('payment') || e.includes('transaction')) return <CreditCard className="h-3.5 w-3.5 text-slate-500" />;
    if (e.includes('message') || e.includes('chat')) return <MessageSquare className="h-3.5 w-3.5 text-slate-500" />;
    if (e.includes('kyc') || e.includes('verification') || e.includes('identity')) return <ShieldCheck className="h-3.5 w-3.5 text-blue-500" />;
    if (e.includes('notification')) return <Bell className="h-3.5 w-3.5 text-amber-500" />;
    return <Activity className="h-3.5 w-3.5 text-slate-400" />;
}

export interface ActivityTimelineProps {
    items: ActivityTimelineItem[];
    className?: string;
    maxItems?: number;
}

/**
 * ActivityTimeline — renders a vertical timeline of activity events.
 * Extracted from ERP/Dashboard.tsx for reuse across pages.
 */
export function ActivityTimeline({ items, className, maxItems }: ActivityTimelineProps) {
    const displayed = maxItems ? items.slice(0, maxItems) : items;

    if (!displayed.length) {
        return (
            <p className="text-sm text-slate-400 text-center py-6">{__('general.no_activity_yet')}</p>
        );
    }

    return (
        <div className={cn('relative border-l border-slate-200 pl-6 ml-3 space-y-6 py-2', className)}>
            {displayed.map((item, idx) => {
                const event = item.title || item.event || '';
                const time = item.time || (item.created_at ? formatDateRelative(item.created_at) : '');
                const icon = item.icon || getIconForEvent(event);

                return (
                    <div key={item.id ?? idx} className="relative">
                        <span className="absolute -left-[31px] top-1 bg-white border border-slate-200 rounded-full p-1 flex items-center justify-center shadow-sm">
                            {icon}
                        </span>
                        <div>
                            <div className="flex items-center justify-between text-xs mb-0.5">
                                <span className="font-semibold text-slate-700 capitalize">
                                    {event.replace(/_/g, ' ')}
                                </span>
                                <span className="text-slate-400 font-mono ml-4 shrink-0">{time}</span>
                            </div>
                            {item.description && (
                                <p className="text-[13px] text-slate-500 leading-relaxed">{item.description}</p>
                            )}
                            {item.user && (
                                <span className="text-[11px] bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full mt-1.5 inline-block font-medium">
                                    By {item.user}
                                </span>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
