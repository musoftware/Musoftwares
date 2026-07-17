import React from 'react';
import { getCurrencyMeta } from './currencyMeta';

export interface IsoCurrencyAmountProps {
    amount: number | string;
    currency?: { currency?: string | null } | null;
    size?: 'lg' | 'md' | 'sm';
    className?: string;
}

const SIZE_STYLES = {
    lg: {
        number: 'text-[28px] font-bold leading-none',
        code: 'text-sm',
        flag: 'h-6 w-6 text-base',
    },
    md: {
        number: 'text-lg font-semibold leading-none',
        code: 'text-[10px]',
        flag: 'h-5 w-5 text-sm',
    },
    sm: {
        number: 'text-sm font-semibold leading-none',
        code: 'text-[9px]',
        flag: 'h-4 w-4 text-xs',
    },
} as const;

export function IsoCurrencyAmount({
    amount,
    currency,
    size = 'md',
    className,
}: IsoCurrencyAmountProps) {
    const meta = getCurrencyMeta(currency?.currency);
    const numericAmount = Number(amount);
    const formattedAmount = new Intl.NumberFormat(
        typeof document !== 'undefined' ? document.documentElement.lang || 'en' : 'en',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        },
    ).format(Number.isFinite(numericAmount) ? numericAmount : 0);
    const styles = SIZE_STYLES[size];

    return (
        <span
            className={`inline-flex items-center gap-1.5 whitespace-nowrap font-sans text-current ${className ?? ''}`}
            style={{ fontFeatureSettings: '"tnum"' }}
        >
            <span
                className={`currency-flag inline-flex shrink-0 items-center justify-center rounded-full bg-slate-100 leading-none ${styles.flag}`}
                title={meta.code}
            >
                <span aria-hidden="true">{meta.flag}</span>
            </span>
            <span className={styles.number}>{formattedAmount}</span>
            <span className={`currency-code font-sans font-medium text-slate-500 ${styles.code}`}>
                {meta.code}
            </span>
        </span>
    );
}
