import React, { useState, useEffect } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';

// Declare route as any since Ziggy is global
declare const route: any;

interface ClientAutocompleteProps {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    className?: string;
    initialClient?: { id: number; name: string } | null;
}

export function ClientAutocomplete({
    value,
    onChange,
    error,
    className,
    initialClient
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

        axios.get(route('erp.clients.search'), {
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
    }, [debouncedSearch, open]);

    return (
        <div className={cn("relative w-full", className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className={cn(
                            "w-full justify-between bg-white border-slate-200 text-slate-900 font-normal hover:bg-slate-50 transition-colors",
                            error && "border-red-500 text-red-900"
                        )}
                    >
                        {selectedName || "Select a Client"}
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[300px] p-0 bg-white border border-slate-200 shadow-md">
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder="Type to search clients..."
                            value={search}
                            onValueChange={setSearch}
                            className="text-slate-900 border-none outline-none focus:ring-0"
                        />
                        <CommandList className="max-h-60 overflow-y-auto">
                            {isLoading && (
                                <div className="flex items-center justify-center p-4 text-xs text-slate-500">
                                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                    Searching...
                                </div>
                            )}
                            
                            {!isLoading && clients.length === 0 && (
                                <CommandEmpty className="py-4 text-center text-xs text-slate-500">
                                    No clients found.
                                </CommandEmpty>
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
