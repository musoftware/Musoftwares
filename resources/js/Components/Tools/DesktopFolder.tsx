import React, { DragEvent } from 'react';

interface DesktopFolderProps {
    id: string;
    name: string;
    childrenTools: { iconUrl: string | null; emoji: string }[];
    onClick?: (e: React.MouseEvent) => void;
    onDoubleClick?: (e: React.MouseEvent) => void;
    onDragStart?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDragEnd?: (e: DragEvent<HTMLDivElement>) => void;
    onDragOver?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDrop?: (e: DragEvent<HTMLDivElement>, id: string) => void;
    onDragLeave?: (e: DragEvent<HTMLDivElement>) => void;
    isDragOver?: boolean;
    style?: React.CSSProperties;
    className?: string;
    isSelected?: boolean;
    onRenameSubmit?: (newName: string) => void;
    onContextMenu?: (e: React.MouseEvent) => void;
    onTouchStart?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchMove?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchEnd?: (e: React.TouchEvent<HTMLDivElement>) => void;
    onTouchCancel?: (e: React.TouchEvent<HTMLDivElement>) => void;
    isEditing?: boolean;
}

export function DesktopFolder({
    id,
    name,
    childrenTools,
    onClick,
    onDoubleClick,
    onDragStart,
    onDragEnd,
    onDragOver,
    onDrop,
    onDragLeave,
    isDragOver = false,
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
}: DesktopFolderProps) {
    const [editValue, setEditValue] = React.useState(name);

    React.useEffect(() => {
        setEditValue(name);
    }, [name, isEditing]);

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            onRenameSubmit?.(editValue);
        } else if (e.key === 'Escape') {
            onRenameSubmit?.(name);
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
            draggable
            onDragStart={(e) => onDragStart?.(e, id)}
            onDragEnd={onDragEnd}
            onDragOver={(e) => onDragOver?.(e, id)}
            onDrop={(e) => onDrop?.(e, id)}
            onDragLeave={onDragLeave}
            style={style}
            className={`flex flex-col items-center gap-1 w-24 p-2 rounded-lg hover:bg-white/20 active:bg-white/30 cursor-pointer transition-all group select-none ${style?.position === 'absolute' ? 'absolute' : 'relative'} ${isDragOver ? 'bg-white/30 scale-105 outline outline-2 outline-blue-400' : ''} ${isSelected ? 'bg-blue-500/40 ring-1 ring-blue-300' : ''} ${className}`}
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
                        {name}
                    </span>
                </div>
            )}
        </div>
    );
}
