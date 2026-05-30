import React from 'react';
import { WorkspaceType } from '../types/snapdownloader.types';
import { navItems } from './Sidebar';

export function MobileNav({
    activeWorkspace,
    setActiveWorkspace,
    automationsCount,
    activeCount,
    queueCount
}: {
    activeWorkspace: WorkspaceType;
    setActiveWorkspace: (ws: WorkspaceType) => void;
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
        <nav
            className="flex md:hidden fixed bottom-0 left-0 right-0 z-20 border-t"
            style={{ background: '#13161f', borderColor: 'rgba(255,255,255,0.08)' }}
        >
            {items.map(item => {
                const active = activeWorkspace === item.id;
                return (
                    <button
                        key={item.id}
                        onClick={() => setActiveWorkspace(item.id)}
                        className="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 relative transition-all"
                        style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)', minHeight: '60px' }}
                    >
                        {/* Badge */}
                        {item.badge !== undefined && item.badge > 0 && (
                            <span className="absolute top-2 right-1/4 w-4 h-4 rounded-full text-[9px] font-black flex items-center justify-center" style={{ background: '#f59e0b', color: '#000' }}>
                                {item.badge}
                            </span>
                        )}
                        <span style={{ color: active ? '#f59e0b' : 'rgba(255,255,255,0.3)' }}>{item.icon}</span>
                        <span className="text-[10px] font-semibold">{item.label}</span>
                        {active && (
                            <span className="absolute bottom-0 left-1/2 -translate-x-1/2 w-8 h-0.5 rounded-full" style={{ background: '#f59e0b' }} />
                        )}
                    </button>
                );
            })}
        </nav>
    );
}
