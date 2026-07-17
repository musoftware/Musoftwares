import React from 'react';
import { cn } from '@/lib/utils';

export interface AvatarStackMember {
    id: number | string;
    name: string;
    avatar_url?: string | null;
    role?: string | null;
}

export interface AvatarStackProps {
    members?: AvatarStackMember[] | null;
    max?: number;
    size?: 'xs' | 'sm' | 'md' | 'lg';
    className?: string;
    emptyLabel?: string;
}

const SIZE_CLASSES: Record<NonNullable<AvatarStackProps['size']>, string> = {
    xs: 'h-6 w-6 text-[10px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-12 w-12 text-base',
};

function initials(name?: string | null): string {
    if (!name) return '?';
    const trimmed = name.trim();
    if (!trimmed) return '?';
    const parts = trimmed.split(/\s+/);
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function avatarPalette(id: number | string): { bg: string; text: string } {
    const palettes = [
        { bg: 'bg-slate-100', text: 'text-slate-700' },
        { bg: 'bg-zinc-100', text: 'text-zinc-700' },
        { bg: 'bg-stone-100', text: 'text-stone-700' },
        { bg: 'bg-neutral-100', text: 'text-neutral-700' },
    ];
    const numeric = typeof id === 'number' ? id : Number(String(id).replace(/\D+/g, '')) || 0;
    return palettes[numeric % palettes.length];
}

export function AvatarStack({
    members = [],
    max = 5,
    size = 'sm',
    className,
    emptyLabel,
}: AvatarStackProps) {
    const list = Array.isArray(members) ? members : [];
    const visible = list.slice(0, max);
    const overflowCount = Math.max(0, list.length - visible.length);
    const dim = SIZE_CLASSES[size];

    if (list.length === 0) {
        return (
            <div
                className={cn(
                    'inline-flex items-center gap-2 rounded-full border border-dashed border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-500',
                    className,
                )}
            >
                <span className={cn('inline-flex items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200', dim)}>
                    ?
                </span>
                <span>{emptyLabel ?? 'No team assigned'}</span>
            </div>
        );
    }

    return (
        <div
            className={cn('inline-flex items-center', className)}
            role="group"
            aria-label="Project team"
        >
            <div className="flex -space-x-2">
                {visible.map((member) => {
                    const palette = avatarPalette(member.id);
                    return (
                        <div
                            key={member.id}
                            className={cn(
                                'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold ring-2 ring-white',
                                dim,
                                member.avatar_url ? 'bg-white' : palette.bg,
                                member.avatar_url ? palette.text : palette.text,
                            )}
                            title={member.role ? `${member.name} · ${member.role}` : member.name}
                            aria-label={member.role ? `${member.name}, ${member.role}` : member.name}
                        >
                            {member.avatar_url ? (
                                <img
                                    src={member.avatar_url}
                                    alt=""
                                    className="h-full w-full object-cover"
                                    onError={(event) => {
                                        const target = event.currentTarget;
                                        target.style.display = 'none';
                                    }}
                                />
                            ) : (
                                <span aria-hidden="true">{initials(member.name)}</span>
                            )}
                        </div>
                    );
                })}
                {overflowCount > 0 && (
                    <div
                        className={cn(
                            'relative inline-flex shrink-0 items-center justify-center rounded-full bg-slate-900 text-white ring-2 ring-white font-semibold',
                            dim,
                        )}
                        title={`+${overflowCount} more`}
                        aria-label={`${overflowCount} more team members`}
                    >
                        +{overflowCount}
                    </div>
                )}
            </div>
        </div>
    );
}

export default AvatarStack;
