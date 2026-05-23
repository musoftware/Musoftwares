import React from 'react';
import {
    Users, Send, LayoutDashboard, Globe, MessageSquare,
    FileText, UsersRound, BarChart3, Bot, Contact, FolderOpen, Radio, Shield
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

type TabId = 'accounts' | 'campaign' | 'groups' | 'group-campaign' | 'history' | 'report' | 'templates' | 'inbox' | 'auto-reply' | 'contacts' | 'dashboard' | 'media' | 'broadcast' | 'deliverability';

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
    unreadInboxCount?: number;
    selectedAccount?: string;
    setSelectedAccount?: (acc: string) => void;
    sessions?: any[];
}

const NAV_ITEMS = [
    { id: 'dashboard',      icon: BarChart3,   label: 'Dashboard',      group: 'main' },
    { id: 'accounts',       icon: Users,       label: 'WA Accounts',    group: 'main' },
    { id: 'campaign',       icon: Send,        label: 'New Campaign',   group: 'main' },
    { id: 'group-campaign', icon: UsersRound,  label: 'Group Campaign', group: 'main' },
    { id: 'templates',      icon: FileText,    label: 'Templates',      group: 'main' },
    { id: 'inbox',          icon: MessageSquare, label: 'Inbox',        group: 'tools' },
    { id: 'auto-reply',     icon: Bot,         label: 'Auto-Reply',     group: 'tools' },
    { id: 'deliverability',  icon: Shield,      label: 'Warmup & Health', group: 'tools' },
    { id: 'contacts',       icon: Contact,     label: 'Contacts',       group: 'tools' },
    { id: 'media',           icon: FolderOpen,  label: 'Media Library',  group: 'tools' },
    { id: 'broadcast',       icon: Radio,       label: 'Direct Send Lists', group: 'tools' },
    { id: 'groups',         icon: UsersRound,  label: 'My Groups',      group: 'tools' },
    { id: 'history',        icon: LayoutDashboard, label: 'Campaigns',  group: 'tools' },
];

function NavButton({ item, active, onClick, badge, label }: any) {
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
            <span className="flex-1 text-start">{label}</span>
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
    runningCampaignsCount = 0,
    unreadInboxCount = 0,
    selectedAccount, setSelectedAccount, sessions
}: SidebarProps) {
    const mainItems = NAV_ITEMS.filter(n => n.group === 'main');
    const toolItems = NAV_ITEMS.filter(n => n.group === 'tools');

    return (
        <>
            {/* Desktop Sidebar */}
            <div className="hidden md:flex flex-col h-screen sticky top-0 w-64 bg-background border-r rtl:border-r-0 rtl:border-l shrink-0 shadow-sm z-20">
                {/* Logo */}
                <div className="p-6 border-b flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-teal-600 flex items-center justify-center shrink-0">
                        <MessageSquare className="w-5 h-5 text-white" />
                    </div>
                    <div className="min-w-0">
                        <h1 className="font-bold text-base tracking-tight leading-none truncate">{t.title}</h1>
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
                        <span className="truncate">{daemonConnected ? t.connected : t.disconnected}</span>
                    </a>
                </div>

                {/* Global Account Selector */}
                {sessions && sessions.length > 0 && (
                    <div className="p-4 border-b bg-muted/20">
                        <label className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-2 flex items-center gap-1.5 px-1">
                            <Users className="w-3 h-3" />
                            {locale === 'ar' ? 'الحساب النشط' : 'Active Account'}
                        </label>
                        <select
                            value={selectedAccount || ''}
                            onChange={e => setSelectedAccount?.(e.target.value)}
                            className="flex h-9 w-full rounded-xl border border-muted bg-background px-3 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 font-bold text-foreground cursor-pointer shadow-sm hover:border-teal-500/50 transition-colors"
                        >
                            <option value="" className="text-muted-foreground font-medium">{locale === 'ar' ? 'كل الحسابات' : 'All Accounts'}</option>
                            {sessions.map((s: any) => (
                                <option key={s.accountId} value={s.accountId} className="font-medium text-foreground">
                                    {s.pushName || s.phone_number || s.accountId}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                {/* Navigation */}
                <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                    <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground px-4 pb-2 text-start">{locale === 'ar' ? 'الإرسال' : 'Sending'}</p>
                    {mainItems.map(item => (
                        <NavButton
                            key={item.id}
                            item={item}
                            label={t.tabs[item.id] || item.label}
                            active={activeTab === item.id}
                            onClick={() => setActiveTab(item.id as TabId)}
                        />
                    ))}

                    <p className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground px-4 pb-2 pt-4 text-start">{locale === 'ar' ? 'الإدارة' : 'Management'}</p>
                    {toolItems.map(item => (
                        <NavButton
                            key={item.id}
                            item={item}
                            label={t.tabs[item.id] || item.label}
                            active={activeTab === item.id}
                            onClick={() => setActiveTab(item.id as TabId)}
                            badge={item.id === 'history' ? runningCampaignsCount : item.id === 'inbox' ? unreadInboxCount : 0}
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
                    { id: 'inbox',          icon: MessageSquare,   label: 'Inbox' },
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
                        <span className="text-[9px] mt-0.5 font-bold truncate w-full text-center">{t.tabs[item.id] || item.label}</span>
                    </button>
                ))}
            </div>
        </>
    );
}
