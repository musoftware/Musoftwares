import React, { useState, useEffect } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { __ } from '@/lib/i18n';

interface AsyncAutocompleteProps {
    value: string | number | null;
    onChange: (value: string | number) => void;
    onSelectFull?: (item: any) => void;
    error?: string;
    className?: string;
    initialItem?: { id: number | string; name: string } | null;
    searchEndpoint: string;
    placeholder?: string;
    emptyText?: string;
    renderItem?: (item: any) => React.ReactNode;
    getDisplayName?: (item: any) => string;
    extraParams?: Record<string, any>;
    disabled?: boolean;
}

export function AsyncAutocomplete({
    value,
    onChange,
    onSelectFull,
    error,
    className,
    initialItem,
    searchEndpoint,
    placeholder = 'Select an item',
    emptyText = 'No items found',
    renderItem,
    getDisplayName,
    extraParams = {},
    disabled = false
}: AsyncAutocompleteProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [items, setItems] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [selectedName, setSelectedName] = useState('');

    useEffect(() => {
        if (initialItem && !selectedName && (!value || String(value) === String(initialItem.id))) {
            setSelectedName(initialItem.name);
        } else if (!value) {
            setSelectedName('');
        }
    }, [initialItem, value]);

    useEffect(() => {
        const handler = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(handler);
    }, [search]);

    useEffect(() => {
        if (!open) return;

        let active = true;
        setIsLoading(true);

        axios.get(searchEndpoint, {
            params: { q: debouncedSearch, ...extraParams }
        })
        .then(response => {
            if (active) setItems(response.data?.data || response.data || []);
        })
        .catch(err => console.error('Error fetching items:', err))
        .finally(() => {
            if (active) setIsLoading(false);
        });

        return () => { active = false; };
    }, [debouncedSearch, open, searchEndpoint, JSON.stringify(extraParams)]);

    const displayName = (item: any) => getDisplayName ? getDisplayName(item) : item.name;

    return (
        <div className={cn("relative w-full", className)}>
            <Popover open={!disabled && open} onOpenChange={setOpen}>
                <PopoverTrigger
                    type="button"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn(
                        buttonVariants({ variant: "outline" }),
                        "w-full justify-between bg-white border-slate-200 text-slate-900 font-normal hover:bg-slate-50 transition-colors",
                        error && "border-red-500 text-red-900",
                        disabled && "opacity-50 cursor-not-allowed bg-slate-50"
                    )}
                >
                    <span className="truncate">{selectedName || placeholder}</span>
                    <ChevronsUpDown className="ms-2 h-4 w-4 shrink-0 opacity-50" />
                </PopoverTrigger>
                <PopoverContent className="w-[300px] p-0 bg-white border border-slate-200 shadow-md z-[100]">
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder="Type to search..."
                            value={search}
                            onValueChange={setSearch}
                            className="text-slate-900 border-none outline-none focus:ring-0"
                        />
                        <CommandList className="max-h-60 overflow-y-auto">
                            {isLoading && (
                                <div className="flex items-center justify-center p-4 text-xs text-slate-500">
                                    <Loader2 className="h-4 w-4 animate-spin me-2" /> Searching...
                                </div>
                            )}
                            
                            {!isLoading && items.length === 0 && (
                                <CommandEmpty className="py-4 text-center text-xs text-slate-500">{emptyText}</CommandEmpty>
                            )}

                            {!isLoading && items.length > 0 && (
                                <CommandGroup>
                                    {items.map((item) => (
                                        <CommandItem
                                            key={item.id}
                                            value={item.id.toString()}
                                            onSelect={() => {
                                                onChange(item.id);
                                                setSelectedName(displayName(item));
                                                if (onSelectFull) onSelectFull(item);
                                                setOpen(false);
                                            }}
                                            className="cursor-pointer hover:bg-slate-100 flex items-center justify-between text-slate-900 px-3 py-2 rounded-md"
                                        >
                                            {renderItem ? renderItem(item) : (
                                                <div className="flex flex-col truncate">
                                                    <span className="font-medium text-slate-900 truncate">{item.name}</span>
                                                    {item.email && <span className="text-xs text-slate-500 truncate">{item.email}</span>}
                                                </div>
                                            )}
                                            {String(value) === String(item.id) && (
                                                <Check className="h-4 w-4 text-emerald-500 flex-shrink-0" />
                                            )}
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            )}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
}
