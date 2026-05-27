import React from 'react';
import { X, Minus, Square } from 'lucide-react';

interface WindowModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    icon?: React.ReactNode;
    children: React.ReactNode;
    width?: string;
    height?: string;
}

export function WindowModal({
    isOpen,
    onClose,
    title,
    icon,
    children,
    width = 'w-[600px]',
    height = 'auto'
}: WindowModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div 
                className={`bg-white rounded-lg shadow-2xl overflow-hidden flex flex-col border border-slate-300 ${width}`}
                style={{ height: height !== 'auto' ? height : undefined, maxHeight: '90vh' }}
            >
                {/* Windows-like Title Bar */}
                <div className="bg-slate-100 flex items-center justify-between px-3 py-2 border-b border-slate-200 select-none">
                    <div className="flex items-center gap-2">
                        {icon && <span className="w-4 h-4 flex items-center justify-center">{icon}</span>}
                        <span className="text-xs font-semibold text-slate-700">{title}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <button className="p-1 hover:bg-slate-200 rounded text-slate-500 transition-colors">
                            <Minus className="w-3 h-3" />
                        </button>
                        <button className="p-1 hover:bg-slate-200 rounded text-slate-500 transition-colors">
                            <Square className="w-3 h-3" />
                        </button>
                        <button 
                            onClick={onClose}
                            className="p-1 hover:bg-red-500 hover:text-white rounded text-slate-500 transition-colors"
                        >
                            <X className="w-3 h-3" />
                        </button>
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 overflow-y-auto p-0 bg-white">
                    {children}
                </div>
            </div>
        </div>
    );
}
