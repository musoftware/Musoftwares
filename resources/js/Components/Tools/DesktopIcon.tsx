import React from 'react';


interface DesktopIconProps {
    title: string;
    iconUrl?: string | null;
    emojiFallback?: string;
    platforms?: string[];
    isFeatured?: boolean;
    isOwned?: boolean;
    isFree?: boolean;
    onClick?: () => void;
    onDoubleClick?: () => void;
}

export function DesktopIcon({
    title,
    iconUrl,
    emojiFallback = '📦',
    platforms = [],
    isFeatured = false,
    isOwned = false,
    isFree = false,
    onClick,
    onDoubleClick
}: DesktopIconProps) {
    return (
        <div 
            onClick={onClick}
            onDoubleClick={onDoubleClick}
            className="flex flex-col items-center gap-1 w-24 p-2 rounded-lg hover:bg-white/20 active:bg-white/30 cursor-pointer transition-colors group select-none relative"
        >
            {isOwned && (
                <div className="absolute top-0 right-1 bg-emerald-500 rounded-full w-3 h-3 border-2 border-slate-900 shadow-sm z-10"></div>
            )}
            
            <div className="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md shadow-lg border border-white/20 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                {iconUrl ? (
                    <img src={iconUrl} alt={title} className="w-8 h-8 object-contain drop-shadow-md" draggable={false} />
                ) : (
                    <span className="text-2xl drop-shadow-md select-none">{emojiFallback}</span>
                )}
            </div>
            
            <div className="text-center w-full">
                <span className="text-white text-xs font-medium leading-tight line-clamp-2 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]" style={{ textShadow: '0 1px 3px rgba(0,0,0,0.8)' }}>
                    {title}
                </span>
            </div>
        </div>
    );
}
