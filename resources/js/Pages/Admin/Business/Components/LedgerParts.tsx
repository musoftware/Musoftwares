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
const periodSelectClass = 'h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-900';

/** Period, then year and month for a single month. Native selects, so the closed box shows the label, not the stored value. */
export function LedgerPeriodSelect({ preset, year, month, availableYears, availableMonths, onPreset, onYear, onMonth }: {
    preset: string;
    year?: number | string;
    month?: number | string;
    availableYears?: number[];
    availableMonths?: number[];
    onPreset: (value: string) => void;
    onYear: (value: string) => void;
    onMonth: (value: string) => void;
}) {
    const isMonth = !preset || preset === 'month';

    return (
        <>
            <select value={isMonth ? 'month' : preset} onChange={(e) => onPreset(e.target.value)} className={periodSelectClass} aria-label={__('admin.ledger_filter_period')}>
                {PERIOD_PRESETS.map((item) => (
                    <option key={item.value} value={item.value}>{__(item.labelKey)}</option>
                ))}
            </select>
            {isMonth && (
                <>
                    <select value={String(month ?? new Date().getMonth() + 1)} onChange={(e) => onMonth(e.target.value)} className={periodSelectClass} aria-label={__('admin.ledger_month')}>
                        {(availableMonths || [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]).map((m) => (
                            <option key={m} value={String(m)}>{monthLabel(m)}</option>
                        ))}
                    </select>
                    <select value={String(year ?? new Date().getFullYear())} onChange={(e) => onYear(e.target.value)} className={periodSelectClass} aria-label={__('admin.ledger_year')}>
                        {(availableYears || [new Date().getFullYear()]).map((y) => (
                            <option key={y} value={String(y)}>{y}</option>
                        ))}
                    </select>
                </>
            )}
        </>
    );
}

/** "22 Oct 2026" / "22 أكتوبر 2026", with Latin digits like the rest of the admin. */
export function ledgerDate(value: string): string {
    const locale = getLoadedLocale() === 'ar' ? 'ar-EG-u-nu-latn' : 'en-GB';
    return new Date(value).toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
}

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
