import React from 'react';
import { HelpCircle } from 'lucide-react';
import { RealtimeLog, LinkedInSession, B2BInbox } from '../../types/b2b.types';

interface SidebarProps {
    realtimeLogs: RealtimeLog[];
    linkedInSession: LinkedInSession;
    inboxes: B2BInbox[];
    runningCampaignIds: string[];
}

export function B2BProspectorSidebar({ realtimeLogs, linkedInSession, inboxes, runningCampaignIds }: SidebarProps) {
    return (
        <aside className="w-72 border-r border-slate-200 bg-white flex flex-col justify-between hidden lg:flex shrink-0">
            <div className="p-5 flex-1 flex flex-col min-h-0">
                <div className="flex items-center gap-2 mb-4">
                    <span className="w-1.5 h-1.5 rounded-full bg-teal-500 animate-ping" />
                    <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400">{__('general.live_activity_feed')}</h3>
                </div>
                <div className="flex-1 overflow-y-auto space-y-3 pr-1 font-sans scrollbar-thin">
                    {realtimeLogs.length === 0 ? (
                        <div className="text-center py-20 text-slate-400 text-xs flex flex-col items-center gap-2">
                            <HelpCircle className="w-6 h-6 text-slate-300" />
                            <span>{__('general.activity_feed_is_clear')}</span>
                            <span>{__('general.launch_a_search_to_harvest_leads')}</span>
                        </div>
                    ) : (
                        realtimeLogs.map(log => (
                            <div key={log.id} className="p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                                <p className="text-slate-800 text-xs leading-relaxed font-medium">{log.message}</p>
                                <span className="text-[10px] text-slate-400 font-mono mt-1 block">{log.time}</span>
                            </div>
                        ))
                    )}
                </div>
            </div>
            
            <div className="p-5 border-t border-slate-100 bg-slate-50/50">
                <div className="space-y-3">
                    <div className="flex items-center justify-between text-xs">
                        <span className="text-slate-500 font-semibold">LinkedIn Scraper:</span>
                        <span className={`font-bold ${linkedInSession.hasSession ? 'text-emerald-600' : 'text-slate-400'}`}>
                            {linkedInSession.hasSession ? 'Linked Key' : 'Not Configured'}
                        </span>
                    </div>
                    <div className="flex items-center justify-between text-xs">
                        <span className="text-slate-500 font-semibold">Mailboxes connected:</span>
                        <span className="font-bold text-slate-900">{inboxes.length} Active</span>
                    </div>
                    <div className="flex items-center justify-between text-xs">
                        <span className="text-slate-500 font-semibold">Active Searches:</span>
                        <span className={`font-bold ${runningCampaignIds.length > 0 ? 'text-teal-600 animate-pulse' : 'text-slate-600'}`}>
                            {runningCampaignIds.length} running
                        </span>
                    </div>
                </div>
            </div>
        </aside>
    );
}
