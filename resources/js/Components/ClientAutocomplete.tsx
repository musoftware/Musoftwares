import React, { useState, useEffect } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { __ } from '@/lib/i18n';

// Declare route as any since Ziggy is global
declare const route: any;

interface ClientAutocompleteProps {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    className?: string;
    initialClient?: { id: number; name: string } | null;
    searchEndpoint?: string;
    placeholder?: string;
}

export function ClientAutocomplete({
    value,
    onChange,
    error,
    className,
    initialClient,
    searchEndpoint,
    placeholder
}: ClientAutocompleteProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [clients, setClients] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [selectedName, setSelectedName] = useState('');

    // Pre-fill initial client name on mount/prop change
    useEffect(() => {
        if (initialClient) {
            setSelectedName(initialClient.name);
        } else if (!value) {
            setSelectedName('');
        }
    }, [initialClient, value]);

    // Debounce search input
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSearch(search);
        }, 300);
        return () => clearTimeout(handler);
    }, [search]);

    // Fetch clients
    useEffect(() => {
        // Only fetch when open
        if (!open) return;

        let active = true;
        setIsLoading(true);

        const endpoint = searchEndpoint || route('erp.clients.search');

        axios.get(endpoint, {
            params: { q: debouncedSearch }
        })
        .then(response => {
            if (active) {
                setClients(response.data || []);
            }
        })
        .catch(err => {
            console.error('Error searching clients:', err);
        })
        .finally(() => {
            if (active) {
                setIsLoading(false);
            }
        });

        return () => {
            active = false;
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedSearch, open]);

    return (
        <div className={cn("relative w-full", className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger
                    type="button"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        buttonVariants({ variant: "outline" }),
                        "w-full justify-between bg-white border-slate-200 text-slate-900 font-normal hover:bg-slate-50 transition-colors",
                        error && "border-red-500 text-red-900"
                    )}
                >
                    {selectedName || placeholder || "Select a Client"}
                    <div className="flex items-center">
                        {value && (
                            <div 
                                role="button"
                                tabIndex={0}
                                className="mr-1 h-4 w-4 rounded-sm hover:bg-slate-200 flex items-center justify-center opacity-50 hover:opacity-100" 
                                onClick={(e) => { e.stopPropagation(); onChange(''); setSelectedName(''); setOpen(false); }}
                            >
                                <svg width="12" height="12" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.7816 4.03157C12.0062 3.80702 12.0062 3.44295 11.7816 3.2184C11.5571 2.99385 11.193 2.99385 10.9685 3.2184L7.50005 6.68682L4.03164 3.2184C3.80708 2.99385 3.44301 2.99385 3.21846 3.2184C2.99391 3.44295 2.99391 3.80702 3.21846 4.03157L6.68688 7.49999L3.21846 10.9684C2.99391 11.193 2.99391 11.557 3.21846 11.7816C3.44301 12.0061 3.80708 12.0061 4.03164 11.7816L7.50005 8.31316L10.9685 11.7816C11.193 12.0061 11.5571 12.0061 11.7816 11.7816C12.0062 11.557 12.0062 11.193 11.7816 10.9684L8.31322 7.49999L11.7816 4.03157Z" fill="currentColor" fillRule="evenodd" clipRule="evenodd"></path></svg>
                            </div>
                        )}
                        <ChevronsUpDown className="ms-2 h-4 w-4 shrink-0 opacity-50" />
                    </div>
                </PopoverTrigger>
                <PopoverContent className="w-[300px] p-0 bg-white border border-slate-200 shadow-md">
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder={__('general.type_to_search_clients')}
                            value={search}
                            onValueChange={setSearch}
                            className="text-slate-900 border-none outline-none focus:ring-0"
                        />
                        <CommandList className="max-h-60 overflow-y-auto">
                            {isLoading && (
                                <div className="flex items-center justify-center p-4 text-xs text-slate-500">
                                    <Loader2 className="h-4 w-4 animate-spin me-2" />{__('general.searching')}</div>
                            )}
                            
                            {!isLoading && clients.length === 0 && (
                                <CommandEmpty className="py-4 text-center text-xs text-slate-500">{__('general.no_clients_found')}</CommandEmpty>
                            )}

                            {!isLoading && clients.length > 0 && (
                                <CommandGroup>
                                    {clients.map((client) => (
                                        <CommandItem
                                            key={client.id}
                                            value={client.id.toString()}
                                            onSelect={(currentValue) => {
                                                onChange(currentValue);
                                                setSelectedName(client.name);
                                                setOpen(false);
                                            }}
                                            className="cursor-pointer hover:bg-slate-100 flex items-center justify-between text-slate-900 px-3 py-2 rounded-md"
                                        >
                                            <div className="flex flex-col">
                                                <span className="font-medium text-slate-900">{client.name}</span>
                                                {client.email && (
                                                    <span className="text-xs text-slate-500">{client.email}</span>
                                                )}
                                            </div>
                                            {value === client.id.toString() && (
                                                <Check className="h-4 w-4 text-emerald-500" />
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
