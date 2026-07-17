import React from 'react';
import { cn } from '@/lib/utils';

export type WatermarkTone = 'paid' | 'unpaid' | 'overdue' | 'draft';

export interface WatermarkStampProps {
    tone?: WatermarkTone;
    label?: string;
    className?: string;
}

const TONE_CLASSES: Record<WatermarkTone, string> = {
    paid: 'text-emerald-500',
    unpaid: 'text-rose-500',
    overdue: 'text-amber-500',
    draft: 'text-slate-400',
};

const TONE_LABELS: Record<WatermarkTone, string> = {
    paid: 'PAID',
    unpaid: 'UNPAID',
    overdue: 'OVERDUE',
    draft: 'DRAFT',
};

export function WatermarkStamp({ tone = 'draft', label, className }: WatermarkStampProps) {
    const text = label ?? TONE_LABELS[tone];
    return (
        <div
            aria-hidden="true"
            className={cn(
                'pointer-events-none hidden select-none items-center justify-center md:flex',
                'absolute end-8 top-6 -z-10',
                'text-[88px] font-black uppercase tracking-[0.2em]',
                'rotate-[-30deg] opacity-10 leading-none',
                TONE_CLASSES[tone],
                className,
            )}
        >
            {text}
        </div>
    );
}

export default WatermarkStamp;
