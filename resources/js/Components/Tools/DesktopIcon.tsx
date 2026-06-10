import React from 'react';
import { Lock } from 'lucide-react';


interface DesktopIconProps {
    title: string;
    iconUrl?: string | null;
    emojiFallback?: string;
    platforms?: string[];
    isFeatured?: boolean;
    isOwned?: boolean;
    isFree?: boolean;
    onClick?: (e: React.MouseEvent) => void;
    onDoubleClick?: (e: React.MouseEvent) => void;
    draggable?: boolean;
    onDragStart?: (e: React.DragEvent<HTMLDivElement>, id: string) => void;
    onDragEnd?: (e: React.DragEvent<HTMLDivElement>) => void;
    onDragOver?: (e: React.DragEvent<HTMLDivElement>, id: string) => void;
    onDrop?: (e: React.DragEvent<HTMLDivElement>, id: string) => void;
    onDragLeave?: (e: React.DragEvent<HTMLDivElement>) => void;
    isDragOver?: boolean;
    id?: string;
    style?: React.CSSProperties;
    className?: string;
    isSelected?: boolean;
    isEditing?: boolean;
    onRenameSubmit?: (newName: string) => void;
    onContextMenu?: (e: React.MouseEvent) => void;
    onTouchStart?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchMove?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchEnd?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchCancel?: (e: React.TouchEvent<HTMLDivElement>) => void;
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
    onDoubleClick,
    draggable = false,
    onDragStart,
    onDragEnd,
    onDragOver,
    onDrop,
    onDragLeave,
    isDragOver = false,
    id,
    style,
    className = '',
    isSelected = false,
    isEditing = false,
    onRenameSubmit,
    onContextMenu,
    onTouchStart,
    onTouchMove,
    onTouchEnd,
    onTouchCancel
}: DesktopIconProps) {
    const [editValue, setEditValue] = React.useState(title);

    React.useEffect(() => {
        setEditValue(title);
    }, [title, isEditing]);

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            onRenameSubmit?.(editValue);
        } else if (e.key === 'Escape') {
            onRenameSubmit?.(title); // revert
        }
    };

    return (
        <div 
            onClick={onClick}
            onDoubleClick={onDoubleClick}
            onContextMenu={onContextMenu}
            onTouchStart={onTouchStart}
            onTouchMove={onTouchMove}
            onTouchEnd={onTouchEnd}
            onTouchCancel={onTouchCancel}
            draggable={draggable}
            onDragStart={(e) => id && onDragStart?.(e, id)}
            onDragEnd={onDragEnd}
            onDragOver={(e) => id && onDragOver?.(e, id)}
            onDrop={(e) => id && onDrop?.(e, id)}
            onDragLeave={onDragLeave}
            style={style}
            className={`flex flex-col items-center gap-1 w-24 p-2 rounded-lg hover:bg-white/20 active:bg-white/30 cursor-pointer transition-all group select-none ${style?.position === 'absolute' ? 'absolute' : 'relative'} ${isDragOver ? 'bg-white/30 scale-105 outline outline-2 outline-blue-400' : ''} ${isSelected ? 'bg-white/20 ring-1 ring-white/50' : ''} ${className}`}
        >
            <div className={`w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md shadow-lg border border-white/20 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform ${!isOwned ? 'grayscale opacity-75' : ''}`}>
                {iconUrl ? (
                    <img src={iconUrl} alt={title} className="w-8 h-8 object-contain drop-shadow-md" draggable={false} />
                ) : (
                    <span className="text-2xl drop-shadow-md select-none">{emojiFallback}</span>
                )}
            </div>

            {!isOwned && (
                <div className="absolute -top-1 right-0 bg-slate-800/90 rounded-full p-[3px] border border-slate-600 shadow-sm backdrop-blur-sm">
                    <Lock className="w-3 h-3 text-slate-300" strokeWidth={2.5} />
                </div>
            )}
            {isOwned && (
                <div className="absolute top-0 right-1 bg-emerald-500 rounded-full w-3 h-3 border-2 border-slate-900 shadow-sm"></div>
            )}
            
            {isEditing ? (
                <input
                    type="text"
                    autoFocus
                    value={editValue}
                    onChange={(e) => setEditValue(e.target.value)}
                    onKeyDown={handleKeyDown}
                    onBlur={() => onRenameSubmit?.(editValue)}
                    className="w-full text-center bg-white/20 text-white text-[11px] rounded px-1 outline-none border border-blue-400"
                    onClick={(e) => e.stopPropagation()}
                    onDoubleClick={(e) => e.stopPropagation()}
                />
            ) : (
                <div className="text-center w-full">
                    <span className="text-white text-xs font-medium leading-tight line-clamp-2 drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]" style={{ textShadow: '0 1px 3px rgba(0,0,0,0.8)' }}>
                        {title}
                    </span>
                </div>
            )}
        </div>
    );
}
