import React from 'react';
import { User } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

export interface AvatarStackMember {
    id: number | string;
    name: string;
    avatar_url?: string | null;
    role?: string | null;
}

export interface AvatarStackProps {
    members?: AvatarStackMember[];
    max?: number;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}

const SIZE_CLASSES = {
    sm: 'h-7 w-7 text-[10px]',
    md: 'h-9 w-9 text-xs',
    lg: 'h-11 w-11 text-sm',
} as const;

function getInitials(name: string): string {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/).slice(0, 2);
    return parts.map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';
}

function getTone(name: string): string {
    const palette = [
        'bg-rose-100 text-rose-700',
        'bg-amber-100 text-amber-700',
        'bg-emerald-100 text-emerald-700',
        'bg-sky-100 text-sky-700',
        'bg-indigo-100 text-indigo-700',
        'bg-violet-100 text-violet-700',
        'bg-teal-100 text-teal-700',
        'bg-orange-100 text-orange-700',
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    }
    return palette[hash % palette.length];
}

export function AvatarStack({ members = [], max = 5, size = 'md', className }: AvatarStackProps) {
    const visible = members.slice(0, max);
    const overflow = members.length - visible.length;
    const sizeClass = SIZE_CLASSES[size];

    if (members.length === 0) {
        return (
            <span
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500',
                    className,
                )}
            >
                <User className="h-3.5 w-3.5" /> Unassigned
            </span>
        );
    }

    return (
        <TooltipProvider delayDuration={200}>
            <div className={cn('flex -space-x-2', className)}>
                {visible.map((member) => {
                    const initials = getInitials(member.name);
                    const tone = getTone(member.name);
                    return (
                        <Tooltip key={member.id}>
                            <TooltipTrigger asChild>
                                <span
                                    role="img"
                                    aria-label={`${member.name}${member.role ? ` · ${member.role}` : ''}`}
                                    className={cn(
                                        'relative inline-flex shrink-0 items-center justify-center rounded-full font-semibold uppercase ring-2 ring-white',
                                        sizeClass,
                                        tone,
                                    )}
                                >
                                    {member.avatar_url ? (
                                        <img
                                            src={member.avatar_url}
                                            alt=""
                                            className="h-full w-full rounded-full object-cover"
                                            onError={(e) => {
                                                (e.currentTarget as HTMLImageElement).style.display = 'none';
                                            }}
                                        />
                                    ) : (
                                        <span aria-hidden="true">{initials}</span>
                                    )}
                                </span>
                            </TooltipTrigger>
                            <TooltipContent side="bottom">
                                <span className="font-medium">{member.name}</span>
                                {member.role && <span className="text-muted-foreground"> · {member.role}</span>}
                            </TooltipContent>
                        </Tooltip>
                    );
                })}
                {overflow > 0 && (
                    <span
                        aria-label={`${overflow} more`}
                        className={cn(
                            'relative inline-flex shrink-0 items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 ring-2 ring-white',
                            sizeClass,
                        )}
                    >
                        +{overflow}
                    </span>
                )}
            </div>
        </TooltipProvider>
    );
}

export default AvatarStack;
