import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { Search, ChevronRight, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface Option {
    value: string | number;
    label: string;
    [key: string]: any;
}

interface PremiumComboboxProps {
    value: string | number | null;
    onChange: (value: string | number | null, option?: Option) => void;
    
    // Data sources
    options?: Option[] | string[] | any[];
    asyncEndpoint?: string;
    searchParam?: string; // default 'q'
    
    // UI
    placeholder?: string;
    searchPlaceholder?: string;
    icon?: React.ReactNode;
    emptyText?: string;
    className?: string;
    
    // Behavior
    debounceMs?: number;
    allowCustomValue?: boolean;
}

export function PremiumCombobox({
    value,
    onChange,
    options = [],
    asyncEndpoint,
    searchParam = 'q',
    placeholder = 'Select an option...',
    searchPlaceholder = 'Search...',
    icon,
    emptyText = 'No results found.',
    className,
    debounceMs = 300,
    allowCustomValue = false,
}: PremiumComboboxProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [asyncOptions, setAsyncOptions] = useState<Option[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    
    const wrapperRef = useRef<HTMLDivElement>(null);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Close on click outside
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Format raw items to Option[]
    const formatOptions = (rawOptions: any[]): Option[] => {
        return rawOptions.map(opt => {
            if (typeof opt === 'string' || typeof opt === 'number') {
                return { value: opt, label: String(opt) };
            }
            // fallback: map id/name if they exist
            return {
                value: opt.id ?? opt.value ?? opt.name,
                label: opt.name ?? opt.label ?? String(opt.id ?? opt.value),
                ...opt
            };
        });
    };

    const staticOptions = formatOptions(options);

    // Async Fetch
    useEffect(() => {
        if (!asyncEndpoint || !isOpen) return;

        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(async () => {
            try {
                setIsLoading(true);
                const res = await axios.get(asyncEndpoint, {
                    params: { [searchParam]: searchQuery }
                });
                const data = res.data?.data || res.data;
                setAsyncOptions(formatOptions(Array.isArray(data) ? data : []));
            } catch (err) {
                console.error("PremiumCombobox fetch error:", err);
            } finally {
                setIsLoading(false);
            }
        }, debounceMs);

        return () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, [searchQuery, isOpen, asyncEndpoint, searchParam, debounceMs]);

    const activeOptions = asyncEndpoint 
        ? asyncOptions 
        : staticOptions.filter(o => o.label.toLowerCase().includes(searchQuery.toLowerCase()));
    
    // Current display label
    let currentLabel = String(value || '');
    if (!allowCustomValue) {
        const found = [...staticOptions, ...asyncOptions].find(o => String(o.value) === String(value));
        if (found) currentLabel = found.label;
    }

    const handleSelect = (val: string | number, opt?: Option) => {
        onChange(val, opt);
        setIsOpen(false);
        setSearchQuery('');
    };

    return (
        <div className={cn("relative w-full text-foreground", className)} ref={wrapperRef}>
            <div
                onClick={() => setIsOpen(!isOpen)}
                className="w-full min-h-11 px-3.5 py-2.5 rounded-xl border bg-background hover:bg-muted/50 transition cursor-pointer flex items-center justify-between text-sm shadow-sm font-medium"
            >
                <div className="flex items-center space-x-2 truncate">
                    {icon && <div className="shrink-0 opacity-70 flex items-center justify-center">{icon}</div>}
                    <span className="truncate">{value ? currentLabel : placeholder}</span>
                </div>
                {isLoading ? (
                    <Loader2 className="w-4 h-4 text-muted-foreground animate-spin shrink-0" />
                ) : (
                    <ChevronRight className={cn("w-4 h-4 text-muted-foreground transition-transform shrink-0", isOpen && "rotate-90")} />
                )}
            </div>

            {isOpen && (
                <div className="absolute top-full start-0 end-0 mt-2 bg-background border rounded-xl shadow-xl z-50 p-2 max-h-60 overflow-y-auto">
                    <div className="relative mb-2">
                        <Search className="absolute start-3 top-2.5 w-4 h-4 text-muted-foreground" />
                        <input
                            type="text"
                            placeholder={searchPlaceholder}
                            value={searchQuery}
                            onChange={(e) => {
                                setSearchQuery(e.target.value);
                                if (allowCustomValue) {
                                    onChange(e.target.value);
                                }
                            }}
                            onClick={(e) => e.stopPropagation()}
                            className="w-full bg-muted rounded-lg ps-9 pe-3 py-1.5 text-xs border-none outline-none focus:ring-2 focus:ring-primary/20 text-foreground"
                        />
                    </div>
                    
                    {activeOptions.length > 0 ? (
                        <div className="space-y-0.5">
                            {activeOptions.map((opt, index) => {
                                const isSelected = String(opt.value) === String(value);
                                return (
                                    <div
                                        key={`${opt.value}-${index}`}
                                        onClick={() => handleSelect(opt.value, opt)}
                                        className={cn(
                                            "px-3 py-2 rounded-lg text-xs font-medium cursor-pointer transition flex items-center space-x-2",
                                            isSelected ? "bg-primary text-primary-foreground font-semibold" : "hover:bg-muted text-foreground"
                                        )}
                                    >
                                        {icon && <div className="shrink-0 opacity-70 flex items-center justify-center w-3.5 h-3.5">{icon}</div>}
                                        <span className="truncate">{opt.label}</span>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="px-3 py-4 text-xs text-center text-muted-foreground">
                            {isLoading ? "Searching..." : emptyText}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
