import React from 'react';
import { Layers } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';

interface HeaderProps {
    activeTab: 'campaigns' | 'leads' | 'inboxes' | 'outreach' | 'linked-profiles';
    setActiveTab: (tab: 'campaigns' | 'leads' | 'inboxes' | 'outreach' | 'linked-profiles') => void;
}

export function B2BProspectorHeader({ activeTab, setActiveTab }: HeaderProps) {
    return (
        <header className="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-30">
            <div className="flex items-center gap-6">
                <div className="flex items-center gap-2.5">
                    <div className="w-6.5 h-6.5 bg-gradient-to-tr from-teal-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md shadow-teal-500/20">
                        <Layers className="w-3.5 h-3.5 text-white" />
                    </div>
                    <span className="font-bold text-sm tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">{__('general.b2b_leads_finder')}</span>
                </div>
                
                <div className="h-5 w-px bg-slate-200" />
                
                <nav className="flex items-center gap-1.5">
                    <Button 
                        variant={activeTab === 'campaigns' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setActiveTab('campaigns')}
                    >{__('general.find_leads')}</Button>
                    <Button 
                        variant={activeTab === 'leads' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setActiveTab('leads')}
                    >{__('general.lead_manager')}</Button>
                    <Button 
                        variant={activeTab === 'inboxes' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setActiveTab('inboxes')}
                    >{__('general.sending_mailboxes')}</Button>
                    <Button 
                        variant={activeTab === 'outreach' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setActiveTab('outreach')}
                    >{__('general.outreach_sequences')}</Button>
                    <Button 
                        variant={activeTab === 'linked-profiles' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setActiveTab('linked-profiles')}
                    >{__('general.linkedin_profile')}</Button>
                </nav>
            </div>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800">
                    <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                    <span className="text-[10px] font-bold uppercase tracking-wider font-mono">{__('general.secure_sqlite_active')}</span>
                </div>
            </div>
        </header>
    );
}
