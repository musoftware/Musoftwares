import React from 'react';
import { cn, formatMoney } from '@/lib/utils';

export interface CurrencyDisplayProps {
    hideSymbol?: boolean;
    amount: number | string;
    currency?: string | { id: number; currency: string; symbol?: string; string_format?: string } | any;
    businessAmount?: number | string;
    businessCurrency?: string | { id: number; currency: string; symbol?: string; string_format?: string } | any;
    size?: 'sm' | 'md' | 'lg';
    colorize?: boolean;
    className?: string;
}

export function CurrencyDisplay({
    amount,
    currency = 'USD',
    businessAmount,
    businessCurrency,
    size = 'md',
    colorize = false,
    className,
}: CurrencyDisplayProps) {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;

    const sizeClasses = {
        sm: 'text-[13px]',
        md: 'text-[14px]',
        lg: 'text-[16px]',
    };

    const secondarySizeClasses = {
        sm: 'text-[11px]',
        md: 'text-[12px]',
        lg: 'text-[13px]',
    };

    let colorClass = 'text-text-primary';
    if (colorize) {
        if (numericAmount > 0) colorClass = 'text-success';
        else if (numericAmount < 0) colorClass = 'text-danger';
    }

    const hasSecondary =
        businessAmount !== undefined &&
        businessCurrency !== undefined &&
        currency !== businessCurrency;

    return (
        <div className={cn('flex flex-col font-mono', className)}>
            <span
                className={cn(
                    'font-medium',
                    sizeClasses[size] || sizeClasses.md,
                    colorClass,
                )}
            >
                {formatMoney(numericAmount, currency)}
            </span>
            {hasSecondary && (
                <span
                    className={cn(
                        'text-text-muted mt-0.5',
                        secondarySizeClasses[size] || secondarySizeClasses.md,
                    )}
                >
                    ≈ {formatMoney(Number(businessAmount), businessCurrency)}
                </span>
            )}
        </div>
    );
}
