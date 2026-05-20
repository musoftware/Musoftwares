import React from 'react';
import { 
    Users, Send, LayoutDashboard, ShieldAlert, 
    Globe, MessageSquare, Power
} from 'lucide-react';

interface SidebarProps {
    activeTab: 'accounts' | 'campaign' | 'deliverability' | 'scorecard';
    setActiveTab: (tab: 'accounts' | 'campaign' | 'deliverability' | 'scorecard') => void;
    locale: 'en' | 'ar';
    setLocale: (locale: 'en' | 'ar') => void;
    daemonConnected: boolean;
    isCampaignRunning: boolean;
    hasResult: boolean;
    t: any;
}

export default function Sidebar({
    activeTab, setActiveTab, locale, setLocale,
    daemonConnected, isCampaignRunning, hasResult, t
}: SidebarProps) {
    return (
        <div className="flex flex-col h-full">
            <div className="p-6 border-b border-slate-100 flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center shadow-md shrink-0">
                    <MessageSquare className="w-5 h-5 text-white" />
                </div>
                <div>
                    <h1 className="font-bold text-base tracking-tight leading-none text-slate-800">{t.title}</h1>
                    <p className="text-[10px] text-slate-400 mt-1 font-medium truncate">{t.subtitle}</p>
                </div>
            </div>

            <div className="p-4 border-b border-slate-100">
                <div className={`flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-semibold uppercase tracking-wider transition-all duration-300 ${
                    daemonConnected 
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                        : 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse'
                }`}>
                    <div className={`w-2 h-2 rounded-full ${daemonConnected ? 'bg-emerald-500 animate-ping' : 'bg-rose-500'}`} />
                    <span>{daemonConnected ? t.connected : t.disconnected}</span>
                </div>
            </div>

            <nav className="flex-1 p-4 space-y-1">
                <button 
                    onClick={() => setActiveTab('accounts')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                        activeTab === 'accounts' 
                            ? 'bg-teal-50 text-teal-700 border border-teal-100' 
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    <Users className="w-4 h-4" />
                    <span>{t.tabs.accounts}</span>
                </button>
                <button 
                    onClick={() => setActiveTab('campaign')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                        activeTab === 'campaign' 
                            ? 'bg-teal-50 text-teal-700 border border-teal-100' 
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    <Send className="w-4 h-4" />
                    <span>{t.tabs.campaign}</span>
                </button>
                <button 
                    onClick={() => setActiveTab('deliverability')}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                        activeTab === 'deliverability' 
                            ? 'bg-teal-50 text-teal-700 border border-teal-100' 
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    <LayoutDashboard className="w-4 h-4" />
                    <span>{t.tabs.deliverability}</span>
                    {isCampaignRunning && (
                        <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping ml-auto" />
                    )}
                </button>
                {hasResult && (
                    <button 
                        onClick={() => setActiveTab('scorecard')}
                        className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                            activeTab === 'scorecard' 
                                ? 'bg-teal-50 text-teal-700 border border-teal-100' 
                                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                        }`}
                    >
                        <ShieldAlert className="w-4 h-4" />
                        <span>{t.tabs.scorecard}</span>
                    </button>
                )}
            </nav>

            <div className="p-4 border-t border-slate-100">
                <button 
                    onClick={() => setLocale(locale === 'en' ? 'ar' : 'en')}
                    className="w-full flex items-center justify-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 transition-all rounded-lg text-xs font-semibold border border-slate-200"
                >
                    <Globe className="w-4 h-4" />
                    <span>{t.language}</span>
                </button>
            </div>
        </div>
    );
}
