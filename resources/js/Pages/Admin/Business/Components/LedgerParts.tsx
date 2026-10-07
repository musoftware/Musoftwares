import React from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { __, getLoadedLocale } from '@/lib/i18n';

export const PERIOD_PRESETS: { value: string; labelKey: string }[] = [
    { value: 'month', labelKey: 'admin.ledger_preset_this_month' },
    { value: 'last_30', labelKey: 'admin.ledger_preset_last_30' },
    { value: 'last_90', labelKey: 'admin.ledger_preset_last_90' },
    { value: 'ytd', labelKey: 'admin.ledger_preset_ytd' },
    { value: 'all', labelKey: 'admin.ledger_preset_all' },
];

export function presetLabel(value: string): string {
    const preset = PERIOD_PRESETS.find((item) => item.value === value);
    return preset ? __(preset.labelKey) : value;
}

/** Month name (1-12) in the active UI language. */
export function monthLabel(month: number): string {
    const locale = getLoadedLocale() || 'en';
    return new Date(2000, month - 1, 1).toLocaleString(locale, { month: 'long' });
}

interface SortHeaderProps {
    field: string;
    sortBy?: string;
    sortDir?: string;
    onSort: (field: string) => void;
    align?: 'start' | 'end' | 'center';
    children: React.ReactNode;
}

export function SortHeader({ field, sortBy, sortDir, onSort, align = 'start', children }: SortHeaderProps) {
    const isActive = sortBy === field;
    const DirectionIcon = sortDir === 'asc' ? ChevronUp : ChevronDown;

    return (
        <button
            type="button"
            onClick={() => onSort(field)}
            className={`inline-flex items-center gap-1 font-semibold text-${align} w-full`}
        >
            {children}
            {isActive && <DirectionIcon className="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />}
        </button>
    );
}

interface ChartTooltipProps {
    active?: boolean;
    payload?: any[];
    label?: any;
    formatValue: (value: number) => string;
}

export function ChartTooltip({ active, payload, label, formatValue }: ChartTooltipProps) {
    if (!active || !payload || payload.length === 0) return null;

    return (
        <div className="bg-black text-white p-3 rounded-lg border border-slate-850 shadow-xl text-xs">
            <p className="font-semibold mb-2 border-b border-slate-800 pb-1">{label}</p>
            {payload.map((entry: any, idx: number) => (
                <div key={idx} className="flex justify-between items-center gap-4 py-0.5">
                    <span className="text-slate-400 capitalize">{entry.name || entry.payload?.name}:</span>
                    <span className="font-mono font-semibold">
                        {typeof entry.value === 'number' ? formatValue(entry.value) : entry.value}
                    </span>
                </div>
            ))}
        </div>
    );
}
