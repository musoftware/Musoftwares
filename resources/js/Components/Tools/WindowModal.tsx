import React, { useState, useEffect, useRef } from 'react';
import { X, Minus, Square, ExternalLink } from 'lucide-react';

interface WindowModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    iconUrl?: string | null;
    children: React.ReactNode;
    isMaximized: boolean;
    onMinimize: () => void;
    onMaximize: () => void;
    onNewTab?: () => void;
    initialX?: number;
    initialY?: number;
    width?: string;
    height?: string;
    zIndex: number;
    onFocus: () => void;
}

export function WindowModal({
    isOpen,
    onClose,
    title,
    iconUrl,
    children,
    isMaximized,
    onMinimize,
    onMaximize,
    onNewTab,
    initialX = 100,
    initialY = 50,
    width = 'w-[800px]',
    height = 'h-[600px]',
    zIndex,
    onFocus
}: WindowModalProps) {
    const [position, setPosition] = useState({ x: initialX, y: initialY });
    const [isDragging, setIsDragging] = useState(false);
    const dragOffset = useRef({ x: 0, y: 0 });

    if (!isOpen) return null;

    const handlePointerDown = (e: React.PointerEvent) => {
        if (isMaximized) return;
        setIsDragging(true);
        onFocus();
        dragOffset.current = {
            x: e.clientX - position.x,
            y: e.clientY - position.y
        };
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
    };

    const handlePointerMove = (e: React.PointerEvent) => {
        if (!isDragging || isMaximized) return;
        setPosition({
            x: Math.max(0, e.clientX - dragOffset.current.x),
            y: Math.max(0, e.clientY - dragOffset.current.y)
        });
    };

    const handlePointerUp = (e: React.PointerEvent) => {
        setIsDragging(false);
        (e.target as HTMLElement).releasePointerCapture(e.pointerId);
    };

    return (
        <div 
            className={`fixed flex flex-col bg-white rounded-lg shadow-2xl overflow-hidden border border-slate-300 transition-all ${isMaximized ? 'inset-0 w-full h-full rounded-none' : `${width} ${height}`}`}
            style={{ 
                left: isMaximized ? 0 : position.x, 
                top: isMaximized ? 0 : position.y,
                zIndex 
            }}
            onPointerDown={onFocus}
        >
            {/* Windows-like Title Bar */}
            <div 
                className={`bg-[#f0f0f0] flex items-center justify-between px-3 h-10 border-b border-slate-300 select-none ${!isMaximized ? 'cursor-move' : ''}`}
                onPointerDown={handlePointerDown}
                onPointerMove={handlePointerMove}
                onPointerUp={handlePointerUp}
                onDoubleClick={onMaximize}
            >
                <div className="flex items-center gap-2 pointer-events-none">
                    {iconUrl ? (
                        <img src={iconUrl} alt={title} className="w-4 h-4 object-contain" draggable={false} />
                    ) : (
                        <span className="w-4 h-4 flex items-center justify-center text-xs">📦</span>
                    )}
                    <span className="text-sm font-medium text-slate-700">{title}</span>
                </div>
                
                <div className="flex items-center h-full">
                    {onNewTab && (
                        <button 
                            onClick={(e) => { e.stopPropagation(); onNewTab(); }}
                            className="h-full px-3 hover:bg-slate-200 text-slate-600 transition-colors flex items-center"
                            title="Open in new tab"
                        >
                            <ExternalLink className="w-3.5 h-3.5" />
                        </button>
                    )}
                    <button 
                        onClick={(e) => { e.stopPropagation(); onMinimize(); }}
                        className="h-full px-4 hover:bg-slate-200 text-slate-600 transition-colors flex items-center"
                        title="Minimize"
                    >
                        <Minus className="w-4 h-4" />
                    </button>
                    <button 
                        onClick={(e) => { e.stopPropagation(); onMaximize(); }}
                        className="h-full px-4 hover:bg-slate-200 text-slate-600 transition-colors flex items-center"
                        title="Maximize"
                    >
                        <Square className="w-3.5 h-3.5" />
                    </button>
                    <button 
                        onClick={(e) => { e.stopPropagation(); onClose(); }}
                        className="h-full px-4 hover:bg-red-500 hover:text-white text-slate-600 transition-colors flex items-center"
                        title="Close"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Content Area */}
            <div className="flex-1 overflow-hidden bg-white relative">
                {/* Pointer events none overlay while dragging iframe prevents iframe from swallowing drag events */}
                {isDragging && <div className="absolute inset-0 z-10" />}
                {children}
            </div>
        </div>
    );
}
