import React from 'react';
import { Download, Clock, Activity, List, FolderOpen, History, Film } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { __ } from '@/lib/i18n';
import { WorkspaceType } from '../types/snapdownloader.types';

export const navItems: { id: WorkspaceType; icon: React.ReactNode; label: string; badge?: number }[] = [
    { id: 'new',         icon: <Download className="w-5 h-5" />,    label: __('Download') },
    { id: 'automations', icon: <Clock className="w-5 h-5" />,       label: __('Automations') },
    { id: 'active',      icon: <Activity className="w-5 h-5" />,    label: __('Active') },
    { id: 'queue',       icon: <List className="w-5 h-5" />,        label: __('Queue') },
    { id: 'folders',     icon: <FolderOpen className="w-5 h-5" />,  label: __('Files') },
    { id: 'history',     icon: <History className="w-5 h-5" />,     label: __('History') },
];

export function Sidebar({
    activeWorkspace,
    setActiveWorkspace,
    connected,
    automationsCount,
    activeCount,
    queueCount
}: {
    activeWorkspace: WorkspaceType;
    setActiveWorkspace: (ws: WorkspaceType) => void;
    connected: boolean;
    automationsCount: number;
    activeCount: number;
    queueCount: number;
}) {
    const items = navItems.map(item => {
        if (item.id === 'automations') item.badge = automationsCount;
        if (item.id === 'active') item.badge = activeCount;
        if (item.id === 'queue') item.badge = queueCount;
        return item;
    });

    return (
        <aside className="hidden md:flex w-56 flex-col border-r shrink-0 sticky top-0 h-screen" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
            {/* Logo */}
            <div className="p-5 border-b" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)' }}>
                        <Film className="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <div className="font-bold text-white text-sm leading-none">{__('SnapDownloader')}</div>
                        <div className="text-[10px] mt-0.5" style={{ color: '#f59e0b' }}>{__('Media Saver')}</div>
                    </div>
                </div>
            </div>

            {/* Nav */}
            <nav className="flex-1 p-3 space-y-0.5">
                {items.map(item => {
                    const active = activeWorkspace === item.id;
                    return (
                        <Button
                            variant="ghost"
                            key={item.id}
                            onClick={() => setActiveWorkspace(item.id)}
                            className="w-full justify-start gap-3 h-11 text-xs font-semibold transition-all"
                            style={{
                                background: active ? 'rgba(245,158,11,0.12)' : 'transparent',
                                color: active ? '#f59e0b' : 'rgba(255,255,255,0.4)',
                            }}
                        >
                            <span style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)' }}>{item.icon}</span>
                            <span className="flex-1 text-left">{item.label}</span>
                            {item.badge !== undefined && item.badge > 0 && (
                                <Badge variant="secondary" className="px-1.5 min-w-[20px] justify-center text-[9px] font-black" style={{ background: '#f59e0b', color: '#000' }}>
                                    {item.badge}
                                </Badge>
                            )}
                        </Button>
                    );
                })}
            </nav>

            {/* Runtime Status */}
            <div className="p-4 border-t" style={{ borderColor: 'rgba(255,255,255,0.06)' }}>
                <div className="flex items-center gap-2.5 px-2">
                    <span className={`w-2 h-2 rounded-full shrink-0 ${connected ? 'animate-pulse' : ''}`} style={{ background: connected ? '#10b981' : '#f43f5e', boxShadow: `0 0 6px ${connected ? '#10b981' : '#f43f5e'}` }} />
                    <div>
                        <div className="text-[10px] font-bold text-white">{connected ? __('Connected') : __('Disconnected')}</div>
                        <div className="text-[9px] mt-0.5" style={{ color: 'rgba(255,255,255,0.3)' }}>{connected ? __('Ready') : __('Offline')}</div>
                    </div>
                </div>
            </div>
        </aside>
    );
}
