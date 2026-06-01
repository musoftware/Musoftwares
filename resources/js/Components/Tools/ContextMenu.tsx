import React, { useEffect, useRef } from 'react';
import { Pencil, Copy, Trash2, Settings, MonitorUp, LayoutGrid, FolderPlus, ArrowRightLeft } from 'lucide-react';

export type ContextMenuType = 'desktop' | 'icon' | 'folder';

export interface ContextMenuState {
    x: number;
    y: number;
    type: ContextMenuType;
    targetId: string | null;
}

interface ContextMenuProps {
    menu: ContextMenuState | null;
    onClose: () => void;
    onAction: (action: string, targetId: string | null) => void;
}

export function ContextMenu({ menu, onClose, onAction }: ContextMenuProps) {
    const menuRef = useRef<HTMLDivElement>(null);

    // Close when clicking outside
    useEffect(() => {
        if (!menu) return;

        const handleClickOutside = (e: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                onClose();
            }
        };

        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [menu, onClose]);

    if (!menu) return null;

    // Keep menu within viewport bounds
    let adjustedX = menu.x;
    let adjustedY = menu.y;
    
    // Approximate menu dimensions
    const menuWidth = 220;
    const menuHeight = 250;
    
    if (typeof window !== 'undefined') {
        if (adjustedX + menuWidth > window.innerWidth) {
            adjustedX = window.innerWidth - menuWidth - 10;
        }
        if (adjustedY + menuHeight > window.innerHeight) {
            adjustedY = window.innerHeight - menuHeight - 10;
        }
    }

    const handleAction = (e: React.MouseEvent, action: string) => {
        e.stopPropagation();
        onAction(action, menu.targetId);
        onClose();
    };

    return (
        <div
            ref={menuRef}
            className="fixed z-[999] w-48 bg-[#1e1e20]/90 backdrop-blur-md border border-white/10 rounded-xl shadow-2xl py-1.5 flex flex-col text-sm text-slate-200 animate-in fade-in zoom-in-95 duration-150"
            style={{ left: adjustedX, top: adjustedY }}
            onContextMenu={(e) => e.preventDefault()}
        >
            {menu.type === 'desktop' && (
                <>
                    <button onClick={(e) => handleAction(e, 'new_folder')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                        <FolderPlus className="w-4 h-4 text-blue-400" />{__('general.new_folder')}</button>
                    <button onClick={(e) => handleAction(e, 'auto_arrange')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                        <LayoutGrid className="w-4 h-4 text-emerald-400" />{__('general.auto_arrange')}</button>
                    <div className="h-px bg-white/10 my-1 mx-2"></div>
                    <button onClick={(e) => handleAction(e, 'settings')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                        <Settings className="w-4 h-4 text-slate-400" /> Settings
                    </button>
                </>
            )}

            {(menu.type === 'icon' || menu.type === 'folder') && (
                <>
                    <button onClick={(e) => handleAction(e, 'open')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left font-medium text-white">
                        <MonitorUp className="w-4 h-4 text-blue-400" /> Open
                    </button>
                    <div className="h-px bg-white/10 my-1 mx-2"></div>
                    <button onClick={(e) => handleAction(e, 'rename')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                        <Pencil className="w-4 h-4 text-amber-400" /> Rename
                    </button>
                    {menu.type === 'icon' && (
                        <button onClick={(e) => handleAction(e, 'duplicate')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                            <Copy className="w-4 h-4 text-slate-400" /> Duplicate
                        </button>
                    )}
                    {menu.type === 'icon' && (
                        <button onClick={(e) => handleAction(e, 'move_to_desktop')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-white/10 transition-colors w-full text-left">
                            <ArrowRightLeft className="w-4 h-4 text-emerald-400" />{__('general.move_to_desktop')}</button>
                    )}
                    <div className="h-px bg-white/10 my-1 mx-2"></div>
                    <button onClick={(e) => handleAction(e, 'delete')} className="flex items-center gap-3 px-3 py-1.5 hover:bg-red-500/20 text-red-400 transition-colors w-full text-left">
                        <Trash2 className="w-4 h-4" /> Delete
                    </button>
                </>
            )}
        </div>
    );
}
