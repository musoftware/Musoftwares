import React from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export interface Currency {
    id: number | string;
    currency: string;
    symbol?: string;
    [key: string]: any;
}

export interface CurrencySelectProps {
    currencies: Currency[];
    value?: string | number;
    onChange: (value: string) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    disabled?: boolean;
    className?: string;
    triggerClassName?: string;
    id?: string;
    valueKey?: 'id' | 'currency';
}

export function CurrencySelect({
    currencies = [],
    value,
    onChange,
    placeholder,
    label,
    error,
    disabled = false,
    className,
    triggerClassName,
    id,
    valueKey = 'id',
}: CurrencySelectProps) {
    return (
        <div className={cn("space-y-2", className)}>
            {label && (
                <Label htmlFor={id} className={error ? 'text-destructive' : ''}>
                    {label}
                </Label>
            )}
            <Select 
                value={value ? String(value) : undefined} 
                onValueChange={(val: any) => onChange(val)} 
                disabled={disabled}
            >
                <SelectTrigger 
                    id={id}
                    className={cn(
                        "w-full",
                        error && "border-destructive focus:ring-destructive",
                        triggerClassName
                    )}
                >
                    <SelectValue placeholder={placeholder || __('general.select_currency')} />
                </SelectTrigger>
                <SelectContent>
                    {currencies.map((c) => (
                        <SelectItem key={c.id} value={String(c[valueKey])}>
                            {c.currency || c.name} {c.symbol ? `(${c.symbol})` : ''}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {error && <p className="text-sm font-medium text-destructive">{error}</p>}
        </div>
    );
}
