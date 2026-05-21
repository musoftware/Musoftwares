import React from 'react';
import {
    Users, Send, LayoutDashboard, Globe, MessageSquare,
    FileText, UsersRound, BarChart3
} from 'lucide-react';

type TabId = 'accounts' | 'campaign' | 'groups' | 'group-campaign' | 'history' | 'report' | 'templates';

interface SidebarProps {
    activeTab: TabId;
    setActiveTab: (tab: TabId) => void;
    locale: 'en' | 'ar';
    setLocale: (locale: 'en' | 'ar') => void;
    daemonConnected: boolean;
    isCampaignRunning: boolean;
    hasResult: boolean;
    t: any;
    runningCampaignsCount?: number;
}

const NAV_ITEMS = [
    { id: 'accounts',       icon: Users,       label: 'WA Accounts',    group: 'main' },
    { id: 'campaign',       icon: Send,        label: 'New Campaign',   group: 'main' },
    { id: 'group-campaign', icon: UsersRound,  label: 'Group Campaign', group: 'main' },
    { id: 'templates',      icon: FileText,    label: 'Templates',      group: 'main' },
    { id: 'groups',         icon: Users,       label: 'My Groups',      group: 'tools' },
    { id: 'history',        icon: LayoutDashboard, label: 'Campaigns',  group: 'tools' },
];

function NavButton({ item, active, onClick, badge }: any) {
    return (
        <button
            onClick={onClick}
            className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all relative ${
                active
                    ? 'bg-gradient-to-r from-teal-50 to-emerald-50/60 text-teal-700 border border-teal-100/80 shadow-[0_2px_8px_rgb(20,184,166,0.08)]'
                    : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'
            }`}
        >
            <item.icon className={`w-4 h-4 transition-transform ${active ? 'scale-105' : ''}`} />
            <span className="flex-1 text-left">{item.label}</span>
            {badge > 0 && (
                <span className="w-5 h-5 rounded-full bg-teal-500 text-white text-[9px] font-black flex items-center justify-center animate-pulse">
                    {badge}
                </span>
            )}
        </button>
    );
}

export default function Sidebar({
    activeTab, setActiveTab, locale, setLocale,
    daemonConnected, isCampaignRunning, hasResult, t,
    runningCampaignsCount = 0
}: SidebarProps) {
    const mainItems = NAV_ITEMS.filter(n => n.group === 'main');
    const toolItems = NAV_ITEMS.filter(n => n.group === 'tools');

    return (
        <>
            {/* Desktop Sidebar */}
            <div className="hidden md:flex flex-col h-screen sticky top-0 w-64 bg-white/70 backdrop-blur-2xl border-r border-slate-200/60 shrink-0 shadow-[4px_0_24px_-12px_rgba(0,0,0,0.05)] z-20">
                {/* Logo */}
                <div className="p-6 border-b border-slate-100 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center shadow-[0_4px_12px_rgb(52,211,153,0.35)] shrink-0">
                        <MessageSquare className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1 className="font-bold text-base tracking-tight leading-none text-slate-800">{t.title}</h1>
                        <p className="text-[10px] text-slate-400 mt-1 font-medium truncate">{t.subtitle}</p>
                    </div>
                </div>

                {/* Runtime status */}
                <div className="p-4 border-b border-slate-100">
                    <div className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-semibold uppercase tracking-wider transition-all duration-300 ${
                        daemonConnected
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse'
                    }`}>
                        <div className={`w-2 h-2 rounded-full flex-shrink-0 ${daemonConnected ? 'bg-emerald-500 animate-ping' : 'bg-rose-500'}`} />
                        <span>{daemonConnected ? t.connected : t.disconnected}</span>
                    </div>
                </div>

                {/* Navigation */}
                <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                    <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-4 pb-2">Sending</p>
                    {mainItems.map(item => (
                        <NavButton
                            key={item.id}
                            item={item}
                            active={activeTab === item.id}
                            onClick={() => setActiveTab(item.id as TabId)}
                        />
                    ))}

                    <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-4 pb-2 pt-4">Management</p>
                    {toolItems.map(item => (
                        <NavButton
                            key={item.id}
                            item={item}
                            active={activeTab === item.id}
                            onClick={() => setActiveTab(item.id as TabId)}
                            badge={item.id === 'history' ? runningCampaignsCount : 0}
                        />
                    ))}
                </nav>

                {/* Footer */}
                <div className="p-4 border-t border-slate-100">
                    <button
                        onClick={() => setLocale(locale === 'en' ? 'ar' : 'en')}
                        className="w-full flex items-center justify-center gap-2 px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all rounded-xl text-xs font-semibold border border-slate-200"
                    >
                        <Globe className="w-4 h-4" />
                        <span>{t.language}</span>
                    </button>
                </div>
            </div>

            {/* Mobile Bottom Navigation */}
            <div className="md:hidden fixed bottom-0 left-0 right-0 z-[100] bg-white/90 backdrop-blur-xl border-t border-slate-200 flex items-center justify-around px-2 py-2 pb-safe shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
                {[
                    { id: 'accounts',       icon: Users,           label: 'Accounts' },
                    { id: 'campaign',       icon: Send,            label: 'Campaign' },
                    { id: 'group-campaign', icon: UsersRound,      label: 'Groups' },
                    { id: 'templates',      icon: FileText,        label: 'Templates' },
                    { id: 'history',        icon: LayoutDashboard, label: 'History' },
                ].map(item => (
                    <button
                        key={item.id}
                        onClick={() => setActiveTab(item.id as TabId)}
                        className={`flex flex-col items-center justify-center w-14 h-14 rounded-xl transition-all ${
                            activeTab === item.id ? 'text-teal-600' : 'text-slate-400 hover:text-slate-600'
                        }`}
                    >
                        <div className={`p-1.5 rounded-lg transition-colors ${activeTab === item.id ? 'bg-teal-50' : ''}`}>
                            <item.icon className={`w-5 h-5 ${activeTab === item.id ? 'scale-110' : ''} transition-transform`} />
                        </div>
                        <span className="text-[9px] mt-0.5 font-bold truncate w-full text-center">{item.label}</span>
                    </button>
                ))}
            </div>
        </>
    );
}
