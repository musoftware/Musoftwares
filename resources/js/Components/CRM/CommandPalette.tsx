import React, { useState, useEffect } from 'react';
import { Command } from 'cmdk';
import { Search, User, CheckSquare, Send, Settings, LogOut } from 'lucide-react';
import { router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

export function CommandPalette() {
    const [open, setOpen] = useState(false);

    // Toggle the menu when ⌘K is pressed
    useEffect(() => {
        const down = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open) => !open);
            }
        };

        document.addEventListener('keydown', down);
        return () => document.removeEventListener('keydown', down);
    }, []);

    const runCommand = (command: () => void) => {
        setOpen(false);
        command();
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm flex items-start justify-center pt-[15vh]">
            <div className="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden border border-slate-200">
                <Command 
                    className="w-full flex flex-col h-full bg-white"
                    onKeyDown={(e) => {
                        if (e.key === 'Escape' || (e.key === 'Backspace' && !(e.target as HTMLInputElement).value)) {
                            e.preventDefault();
                            setOpen(false);
                        }
                    }}
                >
                    <div className="flex items-center border-b border-slate-100 px-3">
                        <Search className="h-5 w-5 text-slate-400 me-2 shrink-0" />
                        <Command.Input 
                            autoFocus
                            placeholder={__('general.search_leads_campaigns_or_actions')} 
                            className="flex-1 h-14 bg-transparent outline-none placeholder:text-slate-400 text-slate-800"
                        />
                        <div className="flex items-center gap-1">
                            <kbd className="bg-slate-100 px-2 py-1 rounded text-xs text-slate-500 font-medium">ESC</kbd>
                        </div>
                    </div>

                    <Command.List className="max-h-[300px] overflow-y-auto p-2">
                        <Command.Empty className="py-6 text-center text-sm text-slate-500">{__('general.no_results_found')}</Command.Empty>

                        <Command.Group heading="Quick Actions" className="text-xs font-medium text-slate-500 p-2">
                            <Command.Item 
                                onSelect={() => runCommand(() => router.visit(route('crm.leads.index')))}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-md cursor-pointer hover:bg-slate-100 aria-selected:bg-slate-100"
                            >
                                <User className="h-4 w-4 text-slate-400" />{__('general.create_new_lead')}</Command.Item>
                            <Command.Item 
                                onSelect={() => runCommand(() => router.visit(route('erp.dashboard')))}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-md cursor-pointer hover:bg-slate-100 aria-selected:bg-slate-100"
                            >
                                <CheckSquare className="h-4 w-4 text-slate-400" />{__('general.create_new_task')}</Command.Item>
                            <Command.Item 
                                onSelect={() => runCommand(() => router.visit(route('crm.campaigns.index')))}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-md cursor-pointer hover:bg-slate-100 aria-selected:bg-slate-100"
                            >
                                <Send className="h-4 w-4 text-slate-400" />{__('general.start_campaign')}</Command.Item>
                        </Command.Group>

                        <Command.Group heading="Navigation" className="text-xs font-medium text-slate-500 p-2">
                            <Command.Item 
                                onSelect={() => runCommand(() => router.visit(route('crm.leads.index')))}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-md cursor-pointer hover:bg-slate-100 aria-selected:bg-slate-100"
                            >{__('general.go_to_leads')}</Command.Item>
                            <Command.Item 
                                onSelect={() => runCommand(() => router.visit(route('crm.dashboard')))}
                                className="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-md cursor-pointer hover:bg-slate-100 aria-selected:bg-slate-100"
                            >{__('general.go_to_dashboard')}</Command.Item>
                        </Command.Group>

                    </Command.List>
                </Command>
            </div>
            {/* Click away overlay */}
            <div className="absolute inset-0 z-[-1]" onClick={() => setOpen(false)} />
        </div>
    );
}
