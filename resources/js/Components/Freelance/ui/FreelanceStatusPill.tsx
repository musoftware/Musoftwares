import React from 'react';
import { cn } from '@/lib/utils';
import { Clock, CheckCircle2, XCircle, ShieldAlert, Activity, AlertCircle } from 'lucide-react';
import { __ } from '@/lib/i18n';

export type FreelanceStatus = 'pending' | 'accepted' | 'rejected' | 'active' | 'completed' | 'disputed' | 'terminated' | string;

export const STATUS_CONFIG: Record<string, { label: string; icon: React.ElementType; className: string }> = {
    pending:    { label: 'Pending',    icon: Clock,        className: 'bg-amber-50 text-amber-700 border-amber-200' },
    accepted:   { label: 'Accepted',   icon: CheckCircle2, className: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    rejected:   { label: 'Rejected',   icon: XCircle,      className: 'bg-red-50 text-red-700 border-red-200' },
    active:     { label: 'Active',     icon: Activity,     className: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    completed:  { label: 'Completed',  icon: CheckCircle2, className: 'bg-blue-50 text-blue-700 border-blue-200' },
    disputed:   { label: 'Disputed',   icon: ShieldAlert,  className: 'bg-red-50 text-red-700 border-red-200' },
    terminated: { label: 'Terminated', icon: XCircle,      className: 'bg-slate-100 text-slate-600 border-slate-200' },
};

export function FreelanceStatusPill({ status, className }: { status: FreelanceStatus; className?: string }) {
    const cfg = STATUS_CONFIG[status] ?? { label: status, icon: AlertCircle, className: 'bg-slate-50 text-slate-600 border-slate-200' };
    const Icon = cfg.icon;
    return (
        <span className={cn('inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border', cfg.className, className)}>
            <Icon className="h-3 w-3" />
            {__(cfg.label)}
        </span>
    );
}
