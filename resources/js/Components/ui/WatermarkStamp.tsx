import React from 'react';
import { cn } from '@/lib/utils';

export type WatermarkTone = 'paid' | 'unpaid' | 'overdue' | 'draft' | 'cancelled';

const TONE_STYLES: Record<WatermarkTone, { color: string; label: string }> = {
    paid: { color: 'text-emerald-300', label: 'Paid' },
    unpaid: { color: 'text-rose-300', label: 'Unpaid' },
    overdue: { color: 'text-amber-300', label: 'Overdue' },
    draft: { color: 'text-slate-300', label: 'Draft' },
    cancelled: { color: 'text-slate-300', label: 'Cancelled' },
};

export interface WatermarkStampProps {
    tone: string;
    label?: string;
    className?: string;
}

function resolveTone(status: string): WatermarkTone {
    const s = (status ?? '').toLowerCase();
    if (s === 'paid' || s === 'partially_paid') return 'paid';
    if (s === 'overdue') return 'overdue';
    if (s === 'draft') return 'draft';
    if (s === 'cancelled' || s === 'canceled' || s === 'refunded') return 'cancelled';
    return 'unpaid';
}

export function WatermarkStamp({ tone, label, className }: WatermarkStampProps) {
    const t = TONE_STYLES[resolveTone(tone)];
    return (
        <div
            aria-hidden="true"
            className={cn(
                'pointer-events-none absolute inset-0 hidden select-none items-center justify-center md:flex',
                className,
            )}
        >
            <span
                className={cn(
                    'rotate-[-30deg] text-[120px] font-black uppercase tracking-widest opacity-15',
                    t.color,
                )}
                style={{ lineHeight: 1 }}
            >
                {label ?? t.label}
            </span>
        </div>
    );
}

export default WatermarkStamp;
