import React from 'react';
import {
    Users, Send, LayoutDashboard, Globe, MessageSquare,
    FileText, UsersRound, BarChart3
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

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
        <Button
            variant={active ? "secondary" : "ghost"}
            onClick={onClick}
            className={`w-full justify-start gap-3 px-4 py-6 rounded-xl font-semibold transition-all relative ${
                active
                    ? 'bg-teal-50 text-teal-700 hover:bg-teal-100 hover:text-teal-800 dark:bg-teal-950/40 dark:text-teal-400'
                    : 'text-muted-foreground hover:text-foreground'
            }`}
        >
            <item.icon className={`w-4 h-4 transition-transform ${active ? 'scale-105' : ''}`} />
            <span className="flex-1 text-left">{item.label}</span>
            {badge > 0 && (
                <Badge variant="default" className="bg-teal-500 hover:bg-teal-600 animate-pulse text-[9px] px-1.5 min-w-5 h-5 flex items-center justify-center rounded-full">
                    {badge}
                </Badge>
            )}
        </Button>
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
            <div className="hidden md:flex flex-col h-screen sticky top-0 w-64 bg-background border-r shrink-0 shadow-sm z-20">
                {/* Logo */}
                <div className="p-6 border-b flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-teal-600 flex items-center justify-center shrink-0">
                        <MessageSquare className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1 className="font-bold text-base tracking-tight leading-none">{t.title}</h1>
                        <p className="text-[10px] text-muted-foreground mt-1 font-medium truncate">{t.subtitle}</p>
                    </div>
                </div>

                {/* Runtime status */}
                <div className="p-4 border-b">
                    <a 
                        href={daemonConnected ? undefined : "musoftware://launch"}
                        className={`flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-semibold uppercase tracking-wider transition-all duration-300 ${
                            daemonConnected
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 cursor-default dark:bg-emerald-950/30 dark:border-emerald-900/50 dark:text-emerald-400'
                                : 'bg-destructive/10 text-destructive border-destructive/20 hover:bg-destructive/20 cursor-pointer shadow-sm hover:shadow-md group'
                        }`}
                        title={daemonConnected ? undefined : "Click to launch the Musoftware Runtime app"}
                    >
                        <div className={`w-2 h-2 rounded-full flex-shrink-0 ${daemonConnected ? 'bg-emerald-500 animate-ping' : 'bg-destructive group-hover:animate-pulse'}`} />
                        <span>{daemonConnected ? t.connected : t.disconnected}</span>
                    </a>
                </div>

                {/* Navigation */}
                <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                    <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground px-4 pb-2">Sending</p>
                    {mainItems.map(item => (
                        <NavButton
                            key={item.id}
                            item={item}
                            active={activeTab === item.id}
                            onClick={() => setActiveTab(item.id as TabId)}
                        />
                    ))}

                    <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground px-4 pb-2 pt-4">Management</p>
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
                <div className="p-4 border-t">
                    <Button
                        variant="outline"
                        onClick={() => setLocale(locale === 'en' ? 'ar' : 'en')}
                        className="w-full justify-center gap-2 rounded-xl"
                    >
                        <Globe className="w-4 h-4" />
                        <span>{t.language}</span>
                    </Button>
                </div>
            </div>

            {/* Mobile Bottom Navigation */}
            <div className="md:hidden fixed bottom-0 left-0 right-0 z-[100] bg-background/90 backdrop-blur-xl border-t flex items-center justify-around px-2 py-2 pb-safe shadow-lg">
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
                            activeTab === item.id ? 'text-teal-600' : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <div className={`p-1.5 rounded-lg transition-colors ${activeTab === item.id ? 'bg-teal-50 dark:bg-teal-950/40' : ''}`}>
                            <item.icon className={`w-5 h-5 ${activeTab === item.id ? 'scale-110' : ''} transition-transform`} />
                        </div>
                        <span className="text-[9px] mt-0.5 font-bold truncate w-full text-center">{item.label}</span>
                    </button>
                ))}
            </div>
        </>
    );
}
