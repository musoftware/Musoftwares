import React, { useState, useEffect, useRef } from 'react';
import { X, Minus, Square, ExternalLink } from 'lucide-react';
import { __ } from '@/lib/i18n';

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
    isMobile?: boolean;
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
    onFocus,
    isMobile = false
}: WindowModalProps) {
    const parseDim = (val: string | number | undefined, defaultVal: number) => {
        if (typeof val === 'number') return val;
        if (!val) return defaultVal;
        const match = val.match(/\d+/);
        return match ? parseInt(match[0], 10) : defaultVal;
    };

    const modalRef = useRef<HTMLDivElement>(null);
    const posRef = useRef({ x: initialX, y: initialY });
    const sizeRef = useRef({ width: parseDim(width, 800), height: parseDim(height, 600) });

    const [isDragging, setIsDragging] = useState(false);
    const [isResizing, setIsResizing] = useState(false);
    
    const dragOffset = useRef({ x: 0, y: 0 });
    const resizeData = useRef({ startW: 0, startH: 0, startX: 0, startY: 0, dir: '' });

    if (!isOpen) return null;

    const isEffectivelyMaximized = isMaximized || isMobile;

    const handlePointerDown = (e: React.PointerEvent) => {
        if (isEffectivelyMaximized) return;
        setIsDragging(true);
        onFocus();
        dragOffset.current = {
            x: e.clientX - posRef.current.x,
            y: e.clientY - posRef.current.y
        };
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
    };

    const handlePointerMove = (e: React.PointerEvent) => {
        if (!isDragging || isEffectivelyMaximized) return;
        posRef.current.x = Math.max(0, e.clientX - dragOffset.current.x);
        posRef.current.y = Math.max(0, e.clientY - dragOffset.current.y);
        
        if (modalRef.current) {
            modalRef.current.style.transform = `translate3d(${posRef.current.x}px, ${posRef.current.y}px, 0)`;
        }
    };

    const handlePointerUp = (e: React.PointerEvent) => {
        setIsDragging(false);
        (e.target as HTMLElement).releasePointerCapture(e.pointerId);
    };

    const handleResizePointerDown = (e: React.PointerEvent, dir: string) => {
        if (isEffectivelyMaximized) return;
        e.stopPropagation();
        setIsResizing(true);
        onFocus();
        resizeData.current = {
            startW: sizeRef.current.width,
            startH: sizeRef.current.height,
            startX: e.clientX,
            startY: e.clientY,
            dir
        };
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
    };

    const handleResizePointerMove = (e: React.PointerEvent) => {
        if (!isResizing || isEffectivelyMaximized) return;
        const dx = e.clientX - resizeData.current.startX;
        const dy = e.clientY - resizeData.current.startY;
        
        if (resizeData.current.dir.includes('e')) {
            sizeRef.current.width = Math.max(300, resizeData.current.startW + dx);
        }
        if (resizeData.current.dir.includes('s')) {
            sizeRef.current.height = Math.max(200, resizeData.current.startH + dy);
        }

        if (modalRef.current) {
            modalRef.current.style.width = `${sizeRef.current.width}px`;
            modalRef.current.style.height = `${sizeRef.current.height}px`;
        }
    };

    const handleResizePointerUp = (e: React.PointerEvent) => {
        setIsResizing(false);
        (e.target as HTMLElement).releasePointerCapture(e.pointerId);
    };

    return (
        <div 
            ref={modalRef}
            className={`fixed flex flex-col bg-white rounded-lg shadow-2xl overflow-hidden border border-slate-300 ${isEffectivelyMaximized ? 'inset-0 rounded-none' : ''}`}
            style={isEffectivelyMaximized ? {
                left: 0,
                top: 0,
                width: '100%',
                height: '100%',
                zIndex,
                transform: 'translate3d(0,0,0)'
            } : {
                left: 0,
                top: 0,
                width: `${sizeRef.current.width}px`,
                height: `${sizeRef.current.height}px`,
                zIndex,
                transform: `translate3d(${posRef.current.x}px, ${posRef.current.y}px, 0)`,
                willChange: 'transform, width, height'
            }}
            onPointerDown={onFocus}
        >
            {/* Windows-like Title Bar */}
            <div 
                className={`bg-[#f0f0f0] flex items-center justify-between px-3 h-12 border-b border-slate-300 select-none touch-none ${!isEffectivelyMaximized ? 'cursor-move' : ''}`}
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
                            className="h-full w-12 hover:bg-slate-200 text-slate-600 transition-colors flex items-center justify-center"
                            title={__('general.open_in_new_tab')}
                        >
                            <ExternalLink className="w-4 h-4" />
                        </button>
                    )}
                    <button 
                        onClick={(e) => { e.stopPropagation(); onMinimize(); }}
                        className="h-full w-12 hover:bg-slate-200 text-slate-600 transition-colors flex items-center justify-center"
                        title="Minimize"
                    >
                        <Minus className="w-4 h-4" />
                    </button>
                    <button 
                        onClick={(e) => { e.stopPropagation(); onMaximize(); }}
                        className="h-full w-12 hover:bg-slate-200 text-slate-600 transition-colors flex items-center justify-center"
                        title="Maximize"
                    >
                        <Square className="w-3.5 h-3.5" />
                    </button>
                    <button 
                        onClick={(e) => { e.stopPropagation(); onClose(); }}
                        className="h-full w-12 hover:bg-red-500 hover:text-white text-slate-600 transition-colors flex items-center justify-center"
                        title="Close"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Content Area */}
            <div className="flex-1 overflow-hidden bg-white relative">
                {/* Pointer events none overlay while dragging iframe prevents iframe from swallowing drag events */}
                {(isDragging || isResizing) && <div className="absolute inset-0 z-10" />}
                {children}
            </div>

            {/* Resize Handles */}
            {!isEffectivelyMaximized && (
                <>
                    <div 
                        className="absolute right-0 top-0 bottom-0 w-3 cursor-e-resize z-20 touch-none"
                        onPointerDown={(e) => handleResizePointerDown(e, 'e')}
                        onPointerMove={handleResizePointerMove}
                        onPointerUp={handleResizePointerUp}
                    />
                    <div 
                        className="absolute left-0 right-0 bottom-0 h-3 cursor-s-resize z-20 touch-none"
                        onPointerDown={(e) => handleResizePointerDown(e, 's')}
                        onPointerMove={handleResizePointerMove}
                        onPointerUp={handleResizePointerUp}
                    />
                    <div 
                        className="absolute right-0 bottom-0 w-5 h-5 cursor-se-resize z-30 touch-none"
                        onPointerDown={(e) => handleResizePointerDown(e, 'se')}
                        onPointerMove={handleResizePointerMove}
                        onPointerUp={handleResizePointerUp}
                    />
                </>
            )}
        </div>
    );
}
