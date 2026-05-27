import React, { DragEvent } from 'react';

interface DesktopFolderProps {
    id: string;
    name: string;
    childrenTools: { iconUrl: string | null; emoji: string }[];
    onClick: () => void;
    onDragStart?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDragOver?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDrop?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDragLeave?: (e: DragEvent<HTMLDivElement>) => void;
    isDragOver?: boolean;
    style?: React.CSSProperties;
}

export function DesktopFolder({
    id,
    name,
    childrenTools,
    onClick,
    onDragStart,
    onDragOver,
    onDrop,
    onDragLeave,
    isDragOver = false,
    style,
}: DesktopFolderProps) {
    return (
        <div 
            onClick={onClick}
            draggable
            onDragStart={(e) => onDragStart?.(e, id)}
            onDragOver={(e) => onDragOver?.(e, id)}
            onDrop={(e) => onDrop?.(e, id)}
            onDragLeave={onDragLeave}
            style={style}
            className={`flex flex-col items-center gap-1 w-24 p-2 rounded-lg hover:bg-white/20 active:bg-white/30 cursor-pointer transition-all group select-none ${style?.position === 'absolute' ? 'absolute' : 'relative'} ${isDragOver ? 'bg-white/30 scale-105 outline outline-2 outline-blue-400' : ''}`}
        >
            <div className="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md shadow-lg border border-white/30 flex flex-wrap items-center justify-center gap-[2px] p-1 overflow-hidden group-hover:scale-105 transition-transform flex-shrink-0">
                {childrenTools.slice(0, 9).map((child, index) => (
                    <div key={index} className="w-3 h-3 flex items-center justify-center text-[8px]">
                        {child.iconUrl ? (
                            <img src={child.iconUrl} alt="" className="w-full h-full object-contain drop-shadow-sm" draggable={false} />
                        ) : (
                            <span className="drop-shadow-sm leading-none">{child.emoji}</span>
                        )}
                    </div>
                ))}
            </div>
            
            <div className="text-center w-full">
                <span className="text-white text-xs font-medium leading-tight line-clamp-2 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]" style={{ textShadow: '0 1px 3px rgba(0,0,0,0.8)' }}>
                    {name}
                </span>
            </div>
        </div>
    );
}
