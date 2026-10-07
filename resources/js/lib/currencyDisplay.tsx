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
    },
    md: {
        number: 'text-lg font-semibold leading-none',
        code: 'text-[10px]',
    },
    sm: {
        number: 'text-sm font-semibold leading-none',
        code: 'text-[9px]',
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
    const validNumber = Number.isFinite(numericAmount) ? numericAmount : 0;
    const rounded = Math.round(validNumber * 100) / 100;
    const isWhole = rounded % 1 === 0;
    const formattedAmount = new Intl.NumberFormat(
        typeof document !== 'undefined' ? document.documentElement.lang || 'en' : 'en',
        {
            minimumFractionDigits: isWhole ? 0 : 2,
            maximumFractionDigits: 2,
        },
    ).format(rounded);
    const styles = SIZE_STYLES[size];

    return (
        <span
            className={`inline-flex items-center gap-1.5 whitespace-nowrap font-sans text-current ${className ?? ''}`}
            style={{ fontFeatureSettings: '"tnum"' }}
        >
            <span className={styles.number}>{formattedAmount}</span>
            <span className={`currency-code font-sans font-medium text-slate-500 ${styles.code}`}>
                {meta.code}
            </span>
        </span>
    );
}
