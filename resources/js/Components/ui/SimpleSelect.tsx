import React from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { cn } from '@/lib/utils';

export interface SimpleSelectOption {
    value: string | number;
    label: string;
}

interface SimpleSelectProps {
    value: string | number | null | undefined;
    onChange: (value: string) => void;
    options: SimpleSelectOption[];
    placeholder?: string;
    className?: string;
    triggerClassName?: string;
    disabled?: boolean;
}

export function SimpleSelect({
    value,
    onChange,
    options = [],
    placeholder = '',
    className,
    triggerClassName,
    disabled = false,
}: SimpleSelectProps) {
    // Find the option matching the current value (comparing as strings)
    const selectedOption = options.find(opt => String(opt.value) === String(value));

    return (
        <div className={cn("relative w-full", className)}>
            <Select
                value={value !== undefined && value !== null ? String(value) : undefined}
                onValueChange={(val) => {
                    if (val !== null && val !== undefined) {
                        onChange(val);
                    }
                }}
                disabled={disabled}
            >
                <SelectTrigger className={cn("w-full h-9 text-xs", triggerClassName)}>
                    <SelectValue placeholder={placeholder}>
                        {selectedOption ? selectedOption.label : undefined}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    {options.map((opt, idx) => (
                        <SelectItem key={`${opt.value}-${idx}`} value={String(opt.value)}>
                            {opt.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
