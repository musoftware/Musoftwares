import React, { useState, useEffect } from 'react';
import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Search, Loader2, Users } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';

export function CrmCommandPalette({ open, setOpen, onOpenLead }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open: boolean) => !open);
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [setOpen]);

    useEffect(() => {
        if (!query || query.length < 2) {
            setResults([]);
            return;
        }

        const delayDebounceFn = setTimeout(async () => {
            setLoading(true);
            try {
                const res = await axios.get(route('crm.search', { q: query }));
                setResults(res.data);
            } catch (error) {
                console.error("Search failed", error);
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(delayDebounceFn);
    }, [query]);

    const handleSelect = (item) => {
        setOpen(false);
        setQuery('');
        
        if (item.type === 'Lead') {
            // Check if we are already on the leads page
            if (window.location.pathname.startsWith('/crm/leads')) {
                onOpenLead(item.action_id);
            } else {
                router.visit(item.url);
            }
        } else {
            router.visit(item.url);
        }
    };

    return (
        <CommandDialog open={open} onOpenChange={setOpen}>
            <CommandInput 
                placeholder={__('general.search_leads_campaigns_notes_ctrl_k')} 
                value={query} 
                onValueChange={setQuery} 
            />
            <CommandList>
                {loading && (
                    <div className="p-4 flex items-center justify-center text-sm text-slate-500">
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />{__('general.searching')}</div>
                )}
                {!loading && query.length >= 2 && results.length === 0 && (
                    <CommandEmpty>{__('general.no_results_found')}</CommandEmpty>
                )}
                
                {results.length > 0 && (
                    <CommandGroup heading="Leads">
                        {results.filter(r => r.type === 'Lead').map((item) => (
                            <CommandItem key={item.id} onSelect={() => handleSelect(item)} className="cursor-pointer">
                                <Users className="mr-2 h-4 w-4 text-indigo-500" />
                                <div>
                                    <span className="font-medium">{item.title}</span>
                                    <span className="ml-2 text-xs text-slate-500">{item.subtitle}</span>
                                </div>
                            </CommandItem>
                        ))}
                    </CommandGroup>
                )}
            </CommandList>
        </CommandDialog>
    );
}
