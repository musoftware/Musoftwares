import React from 'react';
import { Film, RefreshCw, ChevronRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { __ } from '@/lib/i18n';
import { WorkspaceType } from '../types/snapdownloader.types';
import { navItems } from './Sidebar';

export function Header({
    activeWorkspace,
    loadAll,
    activeCount
}: {
    activeWorkspace: WorkspaceType;
    loadAll: () => Promise<void>;
    activeCount: number;
}) {
    const activeLabel = navItems.find(n => n.id === activeWorkspace)?.label || '';

    return (
        <>
            {/* Mobile Header */}
            <header className="flex md:hidden items-center justify-between px-4 py-3 border-b shrink-0 sticky top-0 z-10" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                <div className="flex items-center gap-2.5">
                    <div className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style={{ background: 'linear-gradient(135deg, #f59e0b, #b45309)' }}>
                        <Film className="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <div className="font-bold text-white text-sm leading-none">{__('general.snapdownloader')}</div>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full animate-pulse" style={{ background: '#10b981', boxShadow: '0 0 5px #10b981' }} />
                    <span className="text-[10px] font-semibold" style={{ color: '#10b981' }}>{__('general.live')}</span>
                    <Button variant="ghost" size="icon" onClick={loadAll} className="ms-1 h-8 w-8 rounded-lg" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                        <RefreshCw className="w-3.5 h-3.5" />
                    </Button>
                </div>
            </header>

            {/* Desktop Header */}
            <header className="hidden md:flex h-14 items-center justify-between px-6 border-b shrink-0" style={{ borderColor: 'rgba(255,255,255,0.06)', background: '#13161f' }}>
                <div className="flex items-center gap-2 text-xs">
                    <span className="font-bold text-white capitalize">{activeLabel}</span>
                    <ChevronRight className="w-3.5 h-3.5" style={{ color: 'rgba(255,255,255,0.2)' }} />
                    <span style={{ color: 'rgba(255,255,255,0.35)' }}>
                        {activeCount > 0 ? `${activeCount} ${__('general.active_2')}` : __('general.no_active_downloads')}
                    </span>
                </div>
                <Button variant="ghost" size="sm" onClick={loadAll} className="gap-1.5 h-7 text-[10px] font-semibold transition-all" style={{ background: 'rgba(255,255,255,0.05)', color: 'rgba(255,255,255,0.5)' }}>
                    <RefreshCw className="w-3 h-3" /> {__('general.refresh')}
                </Button>
            </header>
        </>
    );
}

