import React from 'react';
import { cn } from '@/lib/utils';

export interface FilterPillItem {
    id: string;
    label: string;
    count?: number;
}

export interface FilterPillGroupProps {
    items?: Array<string | FilterPillItem>;
    options?: Array<string | FilterPillItem>;
    activeId?: string;
    selected?: string;
    onChange: (id: string) => void;
    className?: string;
    size?: 'sm' | 'md';
}

/**
 * FilterPillGroup — Theme-aware horizontal pill button group for filtering categories and statuses.
 */
export function FilterPillGroup({
    items,
    options,
    activeId,
    selected,
    onChange,
    className,
    size = 'md',
}: FilterPillGroupProps) {
    const rawItems = options || items || [];
    const normalizedItems: FilterPillItem[] = rawItems.map((item) =>
        typeof item === 'string' ? { id: item, label: item } : item
    );
    const currentActiveId = selected !== undefined ? selected : (activeId || '');

    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-xs font-semibold',
    };

    return (
        <div
            data-slot="filter-pill-group"
            className={cn(
                'flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none',
                className
            )}
        >
            {normalizedItems.map((item) => {
                const isSelected = currentActiveId === item.id;

                return (
                    <button
                        key={item.id}
                        type="button"
                        onClick={() => onChange(item.id)}
                        className={cn(
                            'rounded-full whitespace-nowrap transition-all cursor-pointer font-sans select-none',
                            sizeClasses[size],
                            isSelected
                                ? 'bg-[#1d1d1f] dark:bg-white text-white dark:text-zinc-900 shadow-sm font-bold'
                                : 'bg-white dark:bg-zinc-900/90 text-[#1d1d1f]/70 dark:text-zinc-300 border border-black/5 dark:border-white/10 hover:bg-[#f5f5f7] dark:hover:bg-zinc-800 hover:text-[#1d1d1f] dark:hover:text-white'
                        )}
                    >
                        <span>{item.label}</span>
                        {item.count !== undefined && (
                            <span
                                className={cn(
                                    'ms-1.5 px-1.5 py-0.2 rounded-full text-[10px]',
                                    isSelected
                                        ? 'bg-white/20 dark:bg-black/10 text-white dark:text-zinc-900'
                                        : 'bg-black/5 dark:bg-white/10 text-zinc-600 dark:text-zinc-400'
                                )}
                            >
                                {item.count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}

export default FilterPillGroup;
